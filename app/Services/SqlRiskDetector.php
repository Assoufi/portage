<?php

namespace App\Services;

/**
 * Analyseur statique de scripts SQL.
 *
 * Découpe un fichier SQL en statements (en ignorant commentaires et chaînes
 * littérales) puis détecte toute requête destructrice non autorisée.
 * Les statements destructifs sont TOUJOURS bloqués : les opérations
 * destructrices se font via phpMyAdmin, pas par cette interface.
 */
final class SqlRiskDetector
{
    /**
     * Règles de détection (label => regex), appliquées à chaque statement
     * débarrassé de ses commentaires. Sensible à la casse via /i, ancré
     * par frontières de mots.
     */
    private const RISK_PATTERNS = [
        'DROP' => '/\bDROP\b/i',
        'ALTER' => '/\bALTER\b/i',
        'TRUNCATE' => '/\bTRUNCATE\b/i',
        'DELETE' => '/\bDELETE\b/i',
        'RENAME' => '/\bRENAME\b/i',
        'EXEC/EXECUTE' => '/\bEXEC(?:UTE)?\b/i',
        'CALL' => '/\bCALL\b/i',
        'CREATE OR REPLACE' => '/\bCREATE\s+OR\s+REPLACE\b/i',
        'CREATE non autorisé' => '/\bCREATE\s+(?:DATABASE|SCHEMA|VIEW|FUNCTION|PROCEDURE|TRIGGER|USER|EVENT|TABLESPACE)\b/i',
        'GRANT/REVOKE' => '/\bGRANT\b|\bREVOKE\b/i',
        'SET FOREIGN_KEY_CHECKS' => '/\bSET\s+FOREIGN_KEY_CHECKS\b/i',
        'INTO OUTFILE/DUMPFILE' => '/\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i',
        'LOAD DATA/LOAD_FILE' => '/\bLOAD\s+DATA\b|\bLOAD_FILE\b/i',
        'LOCK TABLES' => '/\b(?:UN)?LOCK\s+TABLES\b/i',
        'PREPARE (SQL dynamique)' => '/\bPREPARE\b/i',
        'SHUTDOWN' => '/\bSHUTDOWN\b/i',
        'USE (changement de base)' => '/\bUSE\b/i',
        'DELIMITER (routine)' => '/^\s*DELIMITER\b/im',
    ];

    /**
     * Seuls ces types de statements sont exécutables : tout le reste est
     * bloqué comme « type de requête non autorisé ».
     */
    private const ALLOWED_START_PATTERN = '/^\s*(?:SELECT|INSERT|UPDATE|CREATE\s+TABLE|CREATE\s+(?:UNIQUE\s+)?INDEX)\b/i';

    private const DDL_START_PATTERN = '/^\s*CREATE\b/i';

    /**
     * Analyse complète d'un script normalisé.
     *
     * @return array{
     *     statements: list<array{text: string, offset: int, line: int}>,
     *     risks: list<array{rule: string, line: int, excerpt: string}>,
     *     has_ddl: bool
     * }
     */
    public function analyze(string $sql): array
    {
        $statements = $this->split($sql);
        $risks = [];

        foreach ($statements as $statement) {
            $text = $statement['text'];
            $offset = $statement['offset'];

            if (! preg_match(self::ALLOWED_START_PATTERN, $text)) {
                // Statement hors whitelist : bloqué, mais on analyse aussi
                // son contenu pour détailler précisément les règles violées.
                $risks[] = $this->makeRisk('Type de requête non autorisé', $text, $offset, 0, $sql);
            }

            // Les règles sont appliquées sur le statement « masqué » :
            // le contenu des chaînes littérales est neutralisé pour ne pas
            // détecter des mots-clés SQL dans de simples données (email
            // contenant « exec », valeur « delete me », ...).
            $textMasque = $this->masqueChaines($text);

            // Un UPDATE exige une clause WHERE : sans WHERE, il est destructif.
            if (preg_match('/^\s*UPDATE\b/i', $textMasque) && ! preg_match('/\bWHERE\b/i', $textMasque)) {
                $risks[] = $this->makeRisk('UPDATE sans WHERE', $text, $offset, 0, $sql);
            }

            foreach (self::RISK_PATTERNS as $rule => $pattern) {
                if (preg_match($pattern, $textMasque, $m, PREG_OFFSET_CAPTURE)) {
                    $risks[] = $this->makeRisk($rule, $text, $offset, $m[0][1], $sql);
                }
            }
        }

        $hasDdl = false;
        foreach ($statements as $statement) {
            if (preg_match(self::DDL_START_PATTERN, $statement['text'])) {
                $hasDdl = true;

                break;
            }
        }

        return [
            'statements' => $statements,
            'risks' => $risks,
            'has_ddl' => $hasDdl,
        ];
    }

    /**
     * Découpe le script en statements sur les « ; », en ignorant le contenu
     * des commentaires (-- , #, / * * /) et en protégeant les chaînes
     * ('...', "...", `...`) avec leurs échappements (doubled delim, \\).
     * Retourne pour chaque statement sa position et sa ligne de départ.
     *
     * @return list<array{text: string, offset: int, line: int}>
     */
    public function split(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $start = null;
        $length = strlen($sql);
        $inString = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inString !== null) {
                if ($char === '\\' && $inString !== '`') {
                    // Échappement MySQL dans une chaîne : caractère suivant
                    // absorbé (it\'s, \n, ...).
                    $buffer .= $char.$next;
                    $i++;

                    continue;
                }
                if ($char === $inString) {
                    // Délimiteur doublé ('' ou "") = échappement : on saute
                    // la paire, sinon fermeture de la chaîne.
                    if ($next === $inString) {
                        $buffer .= $char.$next;
                        $i++;

                        continue;
                    }
                    $inString = null;
                }
                $buffer .= $char;

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                if (trim($buffer) === '') {
                    $start = $i;
                }
                $inString = $char;
                $buffer .= $char;

                continue;
            }

            if (($char === '-' && $next === '-') || $char === '#') {
                // Commentaire de ligne : son contenu est retiré du buffer,
                // le saut de ligne final reste compté pour la numérotation.
                $skip = $char === '-' ? 2 : 1;
                $buffer = rtrim($buffer, '-#');
                $i = $this->skipLineComment($sql, $i + $skip, $length);

                continue;
            }

            if ($char === '/' && $next === '*') {
                $buffer = rtrim($buffer, '/');
                $i = $this->skipBlockComment($sql, $i + 2, $length, $buffer);

                continue;
            }

            if ($char === ';') {
                $this->flush($buffer, $start, $sql, $statements);
                $buffer = '';
                $start = null;

                continue;
            }

            if (trim($buffer) === '' && trim($char) !== '') {
                $start = $i;
            }
            $buffer .= $char;
        }

        // Dernier statement sans « ; » final.
        $this->flush($buffer, $start, $sql, $statements);

        return $statements;
    }

    /**
     * @param  list<array{text: string, offset: int, line: int}>  $statements
     */
    private function flush(string $buffer, ?int $start, string $sql, array &$statements): void
    {
        $text = trim($buffer);
        if ($text === '') {
            return;
        }

        $offset = $start ?? 0;
        $statements[] = [
            'text' => $text,
            'offset' => $offset,
            'line' => 1 + substr_count($sql, "\n", 0, $offset),
        ];
    }

    /**
     * Saute `--` ou `#` jusqu'à la fin de ligne (contenu retiré du buffer).
     * Retourne l'index du saut de ligne de fin (ou de la fin du script).
     */
    private function skipLineComment(string $sql, int $position, int $length): int
    {
        while ($position < $length && $sql[$position] !== "\n") {
            $position++;
        }

        return $position;
    }

    /**
     * Neutralise le contenu des chaînes littérales ('...', "...") d'un
     * statement, à longueur identique (les offsets restent donc alignés).
     * Les backticks sont conservés : un mot-clé dans un identifiant
     * (« `use` ») reste suspect. En cas de chaîne jamais fermée, retourne
     * le statement originel.
     */
    private function masqueChaines(string $text): string
    {
        $caractere = [];
        $length = strlen($text);
        $inString = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            $next = $i + 1 < $length ? $text[$i + 1] : '';

            if ($inString !== null) {
                if ($char === chr(92) && $inString !== '`') {
                    // Échappement MySQL : caractère suivant neutralisé aussi.
                    $caractere[$i] = ' ';
                    $caractere[$i + 1] = ' ';
                    $i++;

                    continue;
                }
                if ($char === $inString) {
                    if ($next === $inString) {
                        // Délimiteur doublé = échappement.
                        $caractere[$i] = ' ';
                        $caractere[$i + 1] = ' ';
                        $i++;

                        continue;
                    }
                    $inString = null;
                    $caractere[$i] = $char;

                    continue;
                }
                $caractere[$i] = ' ';

                continue;
            }

            $caractere[$i] = $char;
            if ($char === "'" || $char === '"') {
                $inString = $char;
            }
        }

        return implode('', $caractere);
    }

    /**
     * Saute un commentaire bloc / * ... * / (contenu retiré du buffer,
     * sauf les sauts de ligne qui restent comptés pour la numérotation).
     * Retourne le nouvel index (ou la fin du script si non fermé).
     */
    private function skipBlockComment(string $sql, int $position, int $length, string &$buffer): int
    {
        while ($position < $length) {
            if ($sql[$position] === '*' && $position + 1 < $length && $sql[$position + 1] === '/') {
                return $position + 2;
            }
            if ($sql[$position] === "\n") {
                $buffer .= "\n";
            }
            $position++;
        }

        return $position;
    }

    /**
     * @return array{rule: string, line: int, excerpt: string}
     */
    private function makeRisk(string $rule, string $text, int $offset, int $matchPos, string $sql): array
    {
        return [
            'rule' => $rule,
            'line' => 1 + substr_count($sql, "\n", 0, $offset + $matchPos),
            'excerpt' => $this->excerpt($text),
        ];
    }

    private function excerpt(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        return mb_strlen($text) > 80 ? rtrim(mb_substr($text, 0, 77)).'…' : $text;
    }
}

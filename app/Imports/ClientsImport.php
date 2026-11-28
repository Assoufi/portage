<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/**
 * Lecture du fichier Excel clients.
 *
 * Le parseur ne fait que normaliser les valeurs ; la validation métier et
 * l'écriture en base sont assurées par ClientImportService.
 */
class ClientsImport implements ToCollection, WithCalculatedFormulas
{
    private const CHAMPS = [
        'nom',
        'adresse',
        'type_identification',
        'num_identification',
        'delai_paiement',
        'devise',
        'mode_livraison',
        'remarques',
    ];

    private const COLONNES_IGNOREES = [
        'nom facture',
        'date import',
    ];

    public array $lignes = [];

    public array $ignorees = [];

    public array $invalides = [];

    public int $lignesLues = 0;

    private bool $feuilleLue = false;

    private array $enTetes = [];

    public function collection(Collection $rows): void
    {
        // Seule la première feuille du classeur est exploitée.
        if ($this->feuilleLue) {
            return;
        }

        $this->feuilleLue = true;

        if ($rows->isEmpty()) {
            $this->invalides[] = [
                'ligne' => 1,
                'nom' => null,
                'raison' => 'La feuille est vide.',
            ];

            return;
        }

        $this->extraireEnTetes($rows->first());

        if (empty($this->enTetes)) {
            $this->invalides[] = [
                'ligne' => 1,
                'nom' => null,
                'raison' => 'Entêtes du fichier non reconnus.',
            ];

            return;
        }

        foreach ($rows as $index => $ligne) {
            if ($index === 0) {
                continue;
            }

            $brut = [];

            foreach (self::CHAMPS as $champ) {
                $colonne = $this->enTetes[$champ] ?? null;
                $brut[$champ] = $colonne !== null ? ($ligne[$colonne] ?? null) : null;
            }

            if ($this->ligneVide($brut)) {
                continue;
            }

            $this->lignesLues++;

            $this->lignes[] = [
                'ligne' => (int) $index + 1,
                'nom' => $this->texte($brut['nom']),
                'adresse' => $this->texteOuNull($brut['adresse']),
                'type_identification' => $this->texteOuNull($brut['type_identification']),
                'num_identification' => $this->parserIdentification($brut['num_identification']),
                'delai_paiement' => $this->parserDelai($brut['delai_paiement']),
                'devise' => $this->parserDevise($brut['devise']),
                'mode_livraison' => $this->parserLivraison($brut['mode_livraison']),
                'remarques' => $this->texteOuNull($brut['remarques']),
            ];
        }
    }

    private function extraireEnTetes(Collection $entetes): void
    {
        foreach ($entetes as $index => $brut) {
            $entete = $this->normaliserEntete($brut);

            if ($entete === '') {
                continue;
            }

            if (in_array($entete, self::COLONNES_IGNOREES, true)) {
                $this->ignorees[] = $this->texte($brut);

                continue;
            }

            $champ = match ($entete) {
                'client' => 'nom',
                'adresse' => 'adresse',
                'type identification' => 'type_identification',
                'num identification' => 'num_identification',
                'delai' => 'delai_paiement',
                'devise' => 'devise',
                'livraison', 'mode livraison' => 'mode_livraison',
                'remarques' => 'remarques',
                default => null,
            };

            if ($champ !== null && ! isset($this->enTetes[$champ])) {
                $this->enTetes[$champ] = $index;
            }
        }
    }

    private function normaliserEntete($valeur): string
    {
        $texte = mb_strtolower(trim((string) $valeur));
        $texte = Str::ascii($texte);
        $texte = (string) preg_replace('/[^a-z0-9]+/', ' ', $texte);

        return trim((string) preg_replace('/\s+/', ' ', $texte));
    }

    private function ligneVide(array $ligne): bool
    {
        foreach ($ligne as $valeur) {
            if (is_array($valeur)) {
                if ($valeur !== []) {
                    return false;
                }

                continue;
            }

            if ($valeur !== null && trim((string) $valeur) !== '') {
                return false;
            }
        }

        return true;
    }

    private function texte($valeur): string
    {
        return trim(str_replace("\n", ' ', (string) $valeur));
    }

    private function texteOuNull($valeur): ?string
    {
        $texte = $this->texte($valeur);

        return $texte === '' ? null : $texte;
    }

    private function parserIdentification($valeur): ?string
    {
        $texte = $this->texte($valeur);

        if ($texte === '') {
            return null;
        }

        return strtoupper((string) preg_replace('/\s+/u', '', $texte));
    }

    private function parserDelai($valeur): int|string|null
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (is_int($valeur)) {
            return $valeur;
        }

        if (is_float($valeur)) {
            return floor($valeur) === $valeur ? (int) $valeur : (string) $valeur;
        }

        $texte = trim((string) $valeur);

        if ($texte === '') {
            return null;
        }

        // Une valeur non entière est conservée telle quelle pour que le
        // service puisse la signaler comme invalide.
        return preg_match('/^-?\d+$/', $texte) === 1 ? (int) $texte : $texte;
    }

    private function parserDevise($valeur): ?string
    {
        $texte = mb_strtoupper(trim((string) $valeur));

        if ($texte === '') {
            return null;
        }

        return match ($texte) {
            '€', 'EUR', 'EURO', 'EUROS' => 'EUR',
            'MAD', 'DH', 'DHS', 'DIRHAM', 'DIRHAMS' => 'MAD',
            'USD', 'GBP', 'CAD' => $texte,
            default => $texte,
        };
    }

    private function parserLivraison($valeur): ?array
    {
        $texte = trim((string) $valeur);

        if ($texte === '') {
            return null;
        }

        $elements = preg_split('~[;,/|]+~u', $texte) ?: [];
        $livraisons = [];

        foreach ($elements as $element) {
            $element = trim($element);

            if ($element === '') {
                continue;
            }

            $livraisons[] = match (mb_strtolower($element)) {
                'papier' => 'Papier',
                'email' => 'Email',
                'whatsapp' => 'Whatsapp',
                default => $element,
            };
        }

        return $livraisons === [] ? null : array_values(array_unique($livraisons));
    }
}

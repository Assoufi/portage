<?php

namespace App\Services;

use App\Models\ExecutedScript;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Envoi, analyse et exécution de scripts SQL d'administration.
 *
 * Chaîne de sécurité :
 *  1. Validation HTTP (DatabaseScriptRequest : extension/mime/taille).
 *  2. Normalisation d'encodage (BOM retiré, UTF-8 garanti).
 *  3. Détection de risques par SqlRiskDetector => statut "blocked" si moindre
 *     risque : les opérations destructrices passent par phpMyAdmin.
 *  4. Exécution uniquement après confirmation explicite (checksum ressaisi
 *     + confirmation de sauvegarde), transaction MySQL seulement pour le
 *     pur DML : le DDL (CREATE TABLE/INDEX) provoque un commit implicite.
 *  5. Traçabilité complète en base (audit) + logs applicatifs.
 */
class ScriptExecutionService
{
    public function __construct(
        private readonly SqlRiskDetector $detector
    ) {}

    /**
     * Analyse un fichier envoyé, le stocke hors racine web et crée la
     * trace d'audit correspondante (blocked ou pending).
     */
    public function inspect(UploadedFile $file, User $user, ?string $ip): ExecutedScript
    {
        $content = $this->normalize($file->getContent());
        $analysis = $this->detector->analyze($content);

        $filename = $file->getClientOriginalName();
        $storedPath = 'scripts-sql/'.now()->format('Ymd_His').'_'.Str::random(8).'_'
            .$this->sanitizeName($filename).'.sql';

        Storage::disk('local')->put($storedPath, $content);

        $script = ExecutedScript::create([
            'user_id' => $user->id,
            'filename' => $filename,
            'stored_path' => $storedPath,
            'checksum' => hash('sha256', $content),
            'detected_risks' => $analysis['risks'],
            'status' => $analysis['risks'] === [] ? 'pending' : 'blocked',
            'ip' => $ip,
        ]);

        if ($script->status === 'blocked') {
            Log::warning('ScriptsDatabase.bloque', [
                'script_id' => $script->id,
                'user_id' => $user->id,
                'filename' => $filename,
                'rules' => array_column($analysis['risks'], 'rule'),
                'ip' => $ip,
            ]);
        } else {
            Log::info('ScriptsDatabase.televerse', [
                'script_id' => $script->id,
                'user_id' => $user->id,
                'filename' => $filename,
                'checksum' => $script->checksum,
                'ip' => $ip,
            ]);
        }

        return $script;
    }

    /**
     * Exécute un script préalablement validé et en attente de confirmation.
     * L'intégrité du fichier stocké est revérifiée (sha256) et le contenu
     * repasse l'analyse de risques avant toute exécution.
     *
     * @return array{success: bool, message: string}
     */
    public function execute(ExecutedScript $script): array
    {
        if ($script->status !== 'pending') {
            return ['success' => false, 'message' => 'Ce script a déjà été traité.'];
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($script->stored_path)) {
            $this->marquerEchec($script, 'Fichier source introuvable côté serveur.');

            return ['success' => false, 'message' => 'Fichier source introuvable côté serveur.'];
        }

        $content = $disk->get($script->stored_path);
        if (hash('sha256', $content) !== $script->checksum) {
            $this->marquerEchec($script, 'Intégrité compromise : le fichier stocké a été modifié depuis son envoi (checksum invalide).');

            return ['success' => false, 'message' => 'Checksum invalide : exécution refusée.'];
        }

        $analysis = $this->detector->analyze($content);
        if ($analysis['risks'] !== []) {
            $this->marquerRefus($script, $analysis['risks']);

            return ['success' => false, 'message' => 'Requêtes dangereuses détectées lors du contrôle final : exécution refusée.'];
        }

        $statements = $analysis['statements'];
        if ($statements === []) {
            $this->marquerEchec($script, 'Aucune requête détectée dans le fichier.');

            return ['success' => false, 'message' => 'Aucune requête détectée dans le fichier.'];
        }

        Log::info('ScriptsDatabase.execution_demarree', [
            'script_id' => $script->id,
            'user_id' => $script->user_id,
            'statements' => count($statements),
            'avec_ddl' => $analysis['has_ddl'],
        ]);

        $debut = hrtime(true);
        $sortie = [];
        $lignesAffectees = 0;

        try {
            $pdo = DB::connection()->getPdo();

            $executer = function (string $text, int $ligne) use ($pdo, &$sortie, &$lignesAffectees): void {
                $resultat = $pdo->exec($text);
                if ($resultat === false) {
                    throw new RuntimeException('Échec PDO sur la requête ligne '.$ligne);
                }
                $lignesAffectees += max(0, (int) $resultat);
                $sortie[] = sprintf(
                    'Ligne %d : requête exécutée (%d ligne(s) affectée(s))',
                    $ligne,
                    $resultat
                );
            };

            if ($analysis['has_ddl']) {
                // DDL : commit implicite MySQL, pas de transaction possible.
                foreach ($statements as $statement) {
                    $executer($statement['text'], $statement['line']);
                }
            } else {
                // Pur DML : transaction avec annulation en cas d'échec.
                DB::beginTransaction();
                try {
                    foreach ($statements as $statement) {
                        $executer($statement['text'], $statement['line']);
                    }
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            $script->forceFill([
                'status' => 'completed',
                'output' => implode("\n", $sortie),
                'affected_rows' => $lignesAffectees,
                'duration_ms' => $this->dureeMs($debut),
                'executed_at' => now(),
            ])->save();

            Log::info('ScriptsDatabase.execute', [
                'script_id' => $script->id,
                'user_id' => $script->user_id,
                'affected_rows' => $lignesAffectees,
                'duration_ms' => $script->duration_ms,
            ]);

            return ['success' => true, 'message' => 'Script exécuté avec succès.'];
        } catch (Throwable $e) {
            $script->forceFill([
                'status' => 'failed',
                'output' => implode("\n", $sortie),
                'error_message' => mb_substr(
                    ($analysis['has_ddl'] ? '' : 'La transaction a été annulée : ').$e->getMessage(),
                    0,
                    3000
                ),
                'duration_ms' => $this->dureeMs($debut),
                'executed_at' => now(),
            ])->save();

            Log::error('ScriptsDatabase.echec', [
                'script_id' => $script->id,
                'user_id' => $script->user_id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Exécution interrompue : '.$e->getMessage()];
        }
    }

    private function marquerEchec(ExecutedScript $script, string $erreur): void
    {
        $script->forceFill([
            'status' => 'failed',
            'error_message' => $erreur,
        ])->save();

        Log::error('ScriptsDatabase.echec', [
            'script_id' => $script->id,
            'user_id' => $script->user_id,
            'error' => $erreur,
        ]);
    }

    /**
     * Le contrôle final a détecté un risque : le statut bascule en blocked
     * (jamais en exécution).
     *
     * @param  list<array{rule: string, line: int, excerpt: string}>  $risks
     */
    private function marquerRefus(ExecutedScript $script, array $risks): void
    {
        $script->forceFill([
            'status' => 'blocked',
            'detected_risks' => $risks,
            'error_message' => 'Requêtes dangereuses détectées lors du contrôle final.',
        ])->save();

        Log::warning('ScriptsDatabase.bloque_finale', [
            'script_id' => $script->id,
            'user_id' => $script->user_id,
            'rules' => array_column($risks, 'rule'),
        ]);
    }

    /**
     * Retire le BOM UTF-8, garantit l'encodage UTF-8 et uniformise les
     * fins de ligne.
     */
    private function normalize(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (! mb_check_encoding($content, 'UTF-8')) {
            $converted = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');

            if ($converted === false) {
                throw new RuntimeException('Encodage du fichier non reconnu.');
            }

            $content = $converted;
        }

        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * Nom de fichier assaini (chiffres/lettres/_/-/.), limité à 60 caractères.
     */
    private function sanitizeName(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME) ?: 'script';
        $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base) ?? 'script';
        $base = substr($base, 0, 60);

        return $base === '' || $base === '.sql' ? 'script' : $base;
    }

    private function dureeMs(int $debut): int
    {
        return (int) max(0, intdiv(hrtime(true) - $debut, 1_000_000));
    }
}

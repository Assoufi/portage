<?php

namespace App\Console\Commands;

use App\Imports\FacturesImport;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Services\FactureService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportFacturesCommand extends Command
{
    protected $signature = 'factures:importer
                            {--file=Factures.xlsx : Fichier Excel relatif a storage/app}
                            {--execute : Appliquer les ecritures en base}';

    protected $description = "Importe les factures sans N° Facture d'un fichier Excel (factures + lignes de detail)";

    private array $clientsRef = [];
    private array $fournisseursRef = [];
    private array $numerosSimules = [];

    public function handle(FactureService $service): int
    {
        $chemin = storage_path('app/' . $this->option('file'));

        if (!is_file($chemin)) {
            $this->error("Fichier introuvable : {$chemin}");
            return 1;
        }

        if (!\Schema::hasColumn('factures', 'periode')) {
            $this->error("Colonne 'factures.periode' absente de la base : import impossible.");
            return 1;
        }

        $import = new FacturesImport();
        Excel::import($import, $chemin);

        $execute = (bool) $this->option('execute');

        $this->info($execute
            ? '=== IMPORT factures : exécution réelle ==='
            : '=== IMPORT factures : simulation (utiliser --execute pour appliquer) ===');
        $this->line(sprintf(
            '%d ligne(s) de données | %d ignorée(s) (N° Facture rempli) | %d ligne(s) invalide(s) | %d facture(s) à importer',
            $import->lignesLues,
            count($import->ignorees),
            count($import->invalides),
            count($import->factures)
        ));

        foreach ($import->invalides as $e) {
            $this->warn("  Ligne {$e['ligne']} : {$e['raison']}");
        }

        if (empty($import->factures)) {
            $this->warn('Rien à importer.');
            return 0;
        }

        $this->clientsRef = [];
        $this->fournisseursRef = [];
        $this->numerosSimules = [];

        $rapport = ['importees' => [], 'ecartsTva' => [], 'doublons' => [], 'echecs' => []];

        if ($execute) {
            DB::beginTransaction();
        }

        try {
            foreach ($import->factures as $factureExcel) {
                try {
                    $resultat = $this->importerFacture($factureExcel, $service, $execute, $rapport);

                    if ($resultat !== null) {
                        $rapport['importees'][] = $resultat;
                    }
                } catch (\Throwable $e) {
                    $rapport['echecs'][] = sprintf(
                        'Ligne %s (%s / %s) : %s',
                        $factureExcel['ligne'],
                        $factureExcel['client'],
                        $factureExcel['fournisseur'],
                        $e->getMessage()
                    );
                }
            }

            if ($execute) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if ($execute) {
                DB::rollBack();
            }

            $this->error('Import interrompu (rollback complet) : ' . $e->getMessage());
            $this->reporter($rapport);

            return 1;
        }

        $this->reporter($rapport);

        return 0;
    }

    private function importerFacture(array $f, FactureService $service, bool $execute, array &$rapport): ?array
    {
        if ($f['date'] === null) {
            $rapport['echecs'][] = "Ligne {$f['ligne']} : date de facture absente ou invalide ('{$f['dateBrut']}')";

            return null;
        }

        $client = $this->resoudreClient($f, $execute);
        $fournisseur = $this->resoudreFournisseur($f['fournisseur'], $execute);

        $totalHt = round(collect($f['lignes'])->sum(fn ($d) => $d['quantite'] * $d['prix_unitaire']), 2);
        $taux = (float) $client['tva'];
        $ttcCalcule = round($totalHt * (1 + $taux / 100), 2);
        $excelTtc = round(collect($f['lignes'])->sum(fn ($d) => $d['montant_ttc'] ?? ($d['quantite'] * $d['prix_unitaire'])), 2);

        if (abs($ttcCalcule - $excelTtc) > 0.02) {
            $rapport['ecartsTva'][] = sprintf(
                '%s (%s / %s) : taux client %s %% => TTC calculé %s ≠ TTC Excel %s',
                $f['groupe'] !== '' ? "Groupe {$f['groupe']}" : "Ligne {$f['ligne']}",
                $client['nom'],
                $fournisseur['nom'],
                number_format($taux, 2, ',', ' '),
                number_format($ttcCalcule, 2, ',', ' '),
                number_format($excelTtc, 2, ',', ' ')
            );
        }

        if ($client['id'] !== null) {
            $doublon = Facture::withTrashed()
                ->where('client_id', $client['id'])
                ->whereDate('date_facture', $f['date']->toDateString())
                ->whereRaw('ABS(montant_ttc - ?) < 0.05', [$ttcCalcule])
                ->first();

            if ($doublon) {
                $rapport['doublons'][] = sprintf(
                    'Ligne %s (%s / %s) : facture %s (id %s) déjà en base (client + date + TTC identiques)',
                    $f['ligne'],
                    $client['nom'],
                    $fournisseur['nom'],
                    $doublon->numero_facture,
                    $doublon->id
                );

                return null;
            }
        }

        $echeance = $f['date']->copy()->addDays($client['delai_paiement'] ?? 60)->toDateString();

        if (!$execute) {
            return [
                'numero' => $this->numeroSimule($fournisseur) . ' (prévisionnel)',
                'id' => '-',
                'client' => $client['nom'] . ($client['nouveau'] ? ' [NOUVEAU]' : ''),
                'fournisseur' => $fournisseur['nom'] . ($fournisseur['nouveau'] ? ' [NOUVEAU]' : ''),
                'date' => $f['date']->format('d/m/Y'),
                'periode' => $f['periode']?->format('m/Y') ?: '-',
                'echeance' => $f['date']->copy()->addDays($client['delai_paiement'] ?? 60)->format('d/m/Y'),
                'total_ht' => $totalHt,
                'montant_ttc' => $ttcCalcule,
            ];
        }

        $facture = $service->createFacture([
            'fournisseur_id' => $fournisseur['id'],
            'client_id' => $client['id'],
            'total_ht' => $totalHt,
            'taux_tva' => $taux,
            'montant_ttc' => $ttcCalcule,
            'date_facture' => $f['date']->toDateString(),
            'date_echeance' => $echeance,
            'date_reception' => now()->toDateString(),
            'date_paiement' => $f['paiement_fournisseur']?->toDateString(),
            'date_reglement' => $f['date_paiement_ben']?->toDateString(),
            'reference_reglement' => $f['reference_ben'] !== '' ? $f['reference_ben'] : null,
            'beneficiaire' => $f['beneficiaire'] !== '' ? $f['beneficiaire'] : null,
            'periode' => $f['periode']?->toDateString(),
            'statut' => true,
            'details' => array_map(fn ($d) => [
                'designation' => $d['designation'],
                'quantite' => $d['quantite'],
                'prix_unitaire' => $d['prix_unitaire'],
            ], $f['lignes']),
        ]);

        return [
            'numero' => $facture->numero_facture,
            'id' => $facture->id,
            'client' => $client['nom'] . ($client['nouveau'] ? ' [NOUVEAU]' : ''),
            'fournisseur' => $fournisseur['nom'] . ($fournisseur['nouveau'] ? ' [NOUVEAU]' : ''),
            'date' => $f['date']->format('d/m/Y'),
            'total_ht' => $facture->total_ht,
            'montant_ttc' => $facture->montant_ttc,
        ];
    }

    private function resoudreClient(array $f, bool $execute): array
    {
        $cle = 'client:' . mb_strtolower(trim($f['client']));

        if (isset($this->clientsRef[$cle])) {
            return $this->clientsRef[$cle];
        }

        $client = Client::withTrashed()
            ->whereRaw('LOWER(TRIM(nom)) = ?', [mb_strtolower(trim($f['client']))])
            ->first();

        if ($client) {
            if ($execute && $client->trashed()) {
                $client->restore();
            }

            return $this->clientsRef[$cle] = [
                'id' => $client->id,
                'nom' => $client->nom,
                'nouveau' => false,
                'tva' => (float) $client->tva,
                'delai_paiement' => $client->delai_paiement,
                'devise' => $client->devise,
            ];
        }

        $doc = $f['lignes'][0];
        $tvaDerivee = $this->deriverTva($doc['montant_ttc'], $doc['quantite'], $doc['prix_unitaire']);
        $nomBd = mb_substr(trim($f['client']), 0, 50);

        if (!$execute) {
            return $this->clientsRef[$cle] = [
                'id' => null,
                'nom' => $nomBd,
                'nouveau' => true,
                'tva' => $tvaDerivee,
                'delai_paiement' => null,
                'devise' => str_contains($f['devise'], '€') ? 'EUR' : 'MAD',
            ];
        }

        $client = Client::create([
            'nom' => $nomBd,
            'adresse' => $nomBd,
            'tva' => $tvaDerivee,
            'devise' => str_contains($f['devise'], '€') ? 'EUR' : 'MAD',
            'contact' => $f['contact'] ?: null,
            'periodicite' => $f['periodicite'] !== '' ? mb_substr($f['periodicite'], 0, 50) : null,
            'mode_livraison' => $f['livraison'] !== '' ? [mb_substr($f['livraison'], 0, 50)] : null,
        ]);

        return $this->clientsRef[$cle] = [
            'id' => $client->id,
            'nom' => $client->nom,
            'nouveau' => true,
            'tva' => (float) $client->tva,
            'delai_paiement' => null,
            'devise' => $client->devise,
        ];
    }

    private function resoudreFournisseur(string $nomExcel, bool $execute): array
    {
        $cle = 'fournisseur:' . mb_strtolower(trim($nomExcel));

        if (isset($this->fournisseursRef[$cle])) {
            return $this->fournisseursRef[$cle];
        }

        $fournisseur = Fournisseur::withTrashed()
            ->whereRaw('LOWER(TRIM(nom)) = ?', [mb_strtolower(trim($nomExcel))])
            ->first();

        if ($fournisseur) {
            if ($execute && $fournisseur->trashed()) {
                $fournisseur->restore();
            }

            return $this->fournisseursRef[$cle] = [
                'id' => $fournisseur->id,
                'nom' => $fournisseur->nom,
                'nouveau' => false,
            ];
        }

        $nomBd = mb_substr(trim($nomExcel), 0, 50);

        if (!$execute) {
            return $this->fournisseursRef[$cle] = ['id' => null, 'nom' => $nomBd, 'nouveau' => true];
        }

        $fournisseur = Fournisseur::create(['nom' => $nomBd, 'ice' => $this->genererIceAuto()]);

        return $this->fournisseursRef[$cle] = [
            'id' => $fournisseur->id,
            'nom' => $fournisseur->nom,
            'nouveau' => true,
        ];
    }

    private function deriverTva(?float $ttcExcel, int $quantite, float $prixUnitaire): float
    {
        if ($ttcExcel === null) {
            return 20.0;
        }

        $ht = round($quantite * $prixUnitaire, 2);

        if (abs($ttcExcel - $ht) <= 0.02) {
            return 0.0;
        }

        return 20.0;
    }

    private function genererIceAuto(): string
    {
        for ($n = 1; $n < 1000000; $n++) {
            $ice = 'AUTO' . str_pad((string) $n, 11, '0', STR_PAD_LEFT);

            if (!Fournisseur::withTrashed()->where('ice', $ice)->exists()) {
                return $ice;
            }
        }

        throw new \RuntimeException('Impossible de générer un ICE placeholder unique');
    }

    private function numeroSimule(array $fournisseur): string
    {
        $cle = $fournisseur['nouveau'] ? 'nouveau:' . $fournisseur['nom'] : 'id:' . $fournisseur['id'];

        if (!isset($this->numerosSimules[$cle])) {
            $this->numerosSimules[$cle] = $fournisseur['nouveau']
                ? date('Y') . '-000'
                : Facture::genererNumeroFacturePourFournisseur($fournisseur['id']);
        }

        $parts = explode('-', $this->numerosSimules[$cle]);
        $annee = (int) $parts[0];
        $sequence = (int) ($parts[1] ?? 0);
        $this->numerosSimules[$cle] = $annee . '-' . str_pad((string) ($sequence + 1), 3, '0', STR_PAD_LEFT);

        return $this->numerosSimules[$cle];
    }

    private function reporter(array $rapport): void
    {
        $this->line('');
        $this->info('--- Factures ' . ($rapport['importees'] ? 'prévues / importées' : '') . ' ---');

        foreach ($rapport['importees'] as $f) {
            $this->line(sprintf(
                'N° %s (id %s) | %s | %s / %s | HT %s | TTC %s',
                $f['numero'],
                $f['id'],
                $f['date'],
                $f['client'],
                $f['fournisseur'],
                number_format($f['total_ht'], 2, ',', ' '),
                number_format($f['montant_ttc'], 2, ',', ' ')
            ));
        }

        if ($rapport['doublons']) {
            $this->line('');
            $this->warn('--- Doublons ignorés ---');
            foreach ($rapport['doublons'] as $d) {
                $this->line($d);
            }
        }

        if ($rapport['ecartsTva']) {
            $this->line('');
            $this->warn('--- Écarts TTC / TVA (à vérifier) ---');
            foreach ($rapport['ecartsTva'] as $e) {
                $this->line($e);
            }
        }

        $clientsNouveaux = array_values(array_filter($this->clientsRef, fn ($c) => $c['nouveau']));
        $fournisseursNouveaux = array_values(array_filter($this->fournisseursRef, fn ($fr) => $fr['nouveau']));

        if ($clientsNouveaux) {
            $this->line('');
            $this->info('--- Clients créés automatiquement (à compléter) ---');

            foreach ($clientsNouveaux as $c) {
                $this->line(sprintf('%s | TVA %s %% | devise %s', $c['nom'], number_format($c['tva'], 2), $c['devise']));
            }
        }

        if ($fournisseursNouveaux) {
            $this->line('');
            $this->info('--- Fournisseurs créés automatiquement (ICE placeholder à compléter) ---');

            foreach ($fournisseursNouveaux as $fr) {
                $this->line($fr['nom']);
            }
        }

        if ($rapport['echecs']) {
            $this->line('');
            $this->error('--- Échecs ---');

            foreach ($rapport['echecs'] as $e) {
                $this->line($e);
            }
        }
    }
}

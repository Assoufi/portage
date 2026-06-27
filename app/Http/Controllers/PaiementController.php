<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Fournisseur;
use App\Models\Mission;
use App\Models\Repartition;
use App\Http\Requests\PaiementRequest;
use App\Services\PaiementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    // ─── Taille du lot pour les insertions groupées ───────────────────────────
    private const BATCH_SIZE = 500;

    // ─── Séparateur CSV ───────────────────────────────────────────────────────
    private const CSV_DELIMITER = ';';

    // ─── Mapping des modes de paiement (normalisé) ────────────────────────────
    private const MODE_MAPPING = [
        'virement'          => 'virement',
        'virement bancaire' => 'virement',
        'vir'               => 'virement',        
        'Chéque'            => 'cheque',        
        'especes'           => 'especes',
        'espèces'           => 'especes',
        'versement espece'  => 'especes',
        'versement espèce'  => 'especes',        
        'cash'              => 'especes',        
    ];

    public function __construct(
        private readonly PaiementService $paiementService
    ) {}

    // =========================================================================
    //  CRUD standard (index / create / store / show / edit / update / destroy)
    // =========================================================================

    public function index(Request $request)
    {
        $query = Paiement::with(['client', 'fournisseur', 'mission.consultant']);

        if ($request->filled('client')) {
            $query->whereHas('client', fn($q) => $q->where('nom', 'like', '%' . $request->client . '%'));
        }
        if ($request->filled('fournisseur')) {
            $query->whereHas('fournisseur', fn($q) => $q->where('nom', 'like', '%' . $request->fournisseur . '%'));
        }
        if ($request->filled('consultant')) {
            $query->whereHas('mission.consultant', fn($q) => $q->where('nom', 'like', '%' . $request->consultant . '%'));
        }
        if ($request->filled('capital')) {
            $query->where('montant', 'like', '%' . str_replace(',', '.', $request->capital) . '%');
        }
        if ($request->filled('date_envoi')) {
            $query->whereDate('date_paiement', $request->date_envoi);
        }
        if ($request->filled('date_paiement')) {
            $query->whereHas('repartitions', fn($q) => $q->whereDate('date_paiement', $request->date_paiement));
        }
        if ($request->filled('mode_paiement')) {
            $query->parModePaiement($request->mode_paiement);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut === 'actif');
        }

        $sort      = $request->get('sort', 'date_paiement');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $paiements = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            $html = view('paiements._table', compact('paiements'))->render();
            return response()->json(['html' => $html]);
        }

        return view('paiements.index', compact('paiements'));
    }

    public function create()
    {
        $paiement     = new Paiement();
        $clients      = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $missions     = Mission::with(['client', 'consultant'])->orderBy('date_debut', 'desc')->get();
        $reference    = Paiement::genererReference();

        return view('paiements.create', compact('paiement', 'clients', 'fournisseurs', 'missions', 'reference'));
    }

    public function store(PaiementRequest $request)
    {
        try {
            $paiement = $this->paiementService->createPaiement($request->validated());
            return redirect()->route('paiements.show', $paiement)
                ->with('success', 'Paiement créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la création du paiement : ' . $e->getMessage());
        }
    }

    public function show(Paiement $paiement)
    {
        $paiement->load(['client', 'fournisseur', 'mission.consultant', 'repartitions.consultant']);
        $totalReparti = $paiement->repartitions->sum('montant');
        $soldeRestant = $paiement->montant - $totalReparti;

        return view('paiements.show', compact('paiement', 'totalReparti', 'soldeRestant'));
    }

    public function edit(Paiement $paiement)
    {
        $clients      = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $missions     = Mission::with(['client', 'consultant'])->orderBy('date_debut', 'desc')->get();

        return view('paiements.edit', compact('paiement', 'clients', 'fournisseurs', 'missions'));
    }

    public function update(PaiementRequest $request, Paiement $paiement)
    {
        try {
            $this->paiementService->updatePaiement($paiement, $request->validated());
            return redirect()->route('paiements.index')
                ->with('success', 'Paiement mis à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour du paiement : ' . $e->getMessage());
        }
    }

    public function destroy(Paiement $paiement)
    {
        try {
            $this->paiementService->deletePaiement($paiement);
            return redirect()->route('paiements.index')
                ->with('success', 'Paiement supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression du paiement : ' . $e->getMessage());
        }
    }

    // =========================================================================
    //  IMPORT CSV — version optimisée
    // =========================================================================

    public function importer(Request $request)
    {
        // ── 0. Lever les limites PHP pour les gros fichiers ──────────────────
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $request->validate([
            'fichier' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path   = $request->file('fichier')->getRealPath();
        $handle = fopen($path, 'r');

        // ── 1. Gestion du BOM UTF-8 ──────────────────────────────────────────
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // ── 2. Lecture et nettoyage des en-têtes ─────────────────────────────
        $rawHeader = fgetcsv($handle, 0, self::CSV_DELIMITER);
        if (!$rawHeader) {
            fclose($handle);
            return back()->with('error', 'Fichier CSV invalide ou vide.');
        }
        $header = array_map([$this, 'cleanHeader'], $rawHeader);

        // ── 3. Pré-chargement des référentiels en mémoire ────────────────────
        //    Évite N requêtes firstOrCreate() à chaque ligne.
        $consultantCache  = Consultant::pluck('id', 'nom')->toArray();
        $clientCache      = Client::pluck('id', 'nom')->toArray();
        $fournisseurCache = Fournisseur::pluck('id', 'nom')->toArray();

        // ── 3b. Cache composite des paiements existants ───────────────────────
        //    Clé : "montant|client_id|fournisseur_id|date_paiement"
        //    Élimine le SELECT par ligne (était ~10 000 requêtes).
        $paiementCache = Paiement::select('id', 'montant', 'client_id', 'fournisseur_id', 'date_paiement')
            ->get()
            ->keyBy(fn($p) => "{$p->montant}|{$p->client_id}|{$p->fournisseur_id}|{$p->date_paiement}")
            ->map(fn($p) => $p->id)
            ->toArray();

        // ── 4. Compteurs, tampons et accumulateurs ────────────────────────────
        $creees            = 0;
        $misesAJour        = 0;
        $erreurs           = [];
        $batchRepartitions = [];   // tampon pour l'insertion groupée des répartitions
        $repartitionTotals = [];   // [paiement_id => total cumulé] — évite le SUM() par ligne
        $numLigne          = 1;    // numéro de ligne CSV (hors en-tête)

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, self::CSV_DELIMITER)) !== false) {
                $numLigne++;

                // Ignorer les lignes totalement vides
                if (count($row) < 2 || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }

                // Vérifier la cohérence du nombre de colonnes
                if (count($header) !== count($row)) {
                    $erreurs[] = "Ligne {$numLigne} : nombre de colonnes incohérent ("
                        . count($row) . " au lieu de " . count($header) . ").";
                    continue;
                }

                $data  = array_combine($header, $row);
                $ligne = $data['N#'] ?? $numLigne;

                try {
                    // ── 4a. Nettoyage de toutes les valeurs ──────────────────
                    $data = array_map([$this, 'cleanValue'], $data);

                    // ── 4b. Consultant ────────────────────────────────────────
                    $beneficiaire = $data['Consultant'] ?? '';
                    if ($beneficiaire === '') {
                        throw new \RuntimeException('Consultant manquant.');
                    }
                    if (!isset($consultantCache[$beneficiaire])) {
                        $c = Consultant::firstOrCreate(
                            ['nom' => $beneficiaire],
                            ['statut' => true]
                        );
                        $consultantCache[$beneficiaire] = $c->id;
                    }
                    $consultantId = $consultantCache[$beneficiaire];

                    // ── 4c. Client ────────────────────────────────────────────
                    $nomClient = $data['Client'] ?? '';
                    if ($nomClient === '') {
                        throw new \RuntimeException('Client manquant.');
                    }
                    if (!isset($clientCache[$nomClient])) {
                        $emailClient = 'client.'
                            . strtolower(preg_replace('/[^a-z0-9]/i', '.', $nomClient))
                            . '@import.local';
                        $cl = Client::firstOrCreate(
                            ['nom' => $nomClient],
                            ['email' => $emailClient, 'statut' => true]
                        );
                        $clientCache[$nomClient] = $cl->id;
                    }
                    $clientId = $clientCache[$nomClient];

                    // ── 4d. Fournisseur ───────────────────────────────────────
                    $nomFournisseur = $data['Fournisseur'] ?? '';
                    if ($nomFournisseur === '') {
                        throw new \RuntimeException('Fournisseur manquant.');
                    }
                    if (!isset($fournisseurCache[$nomFournisseur])) {
                        $f = Fournisseur::firstOrCreate(
                            ['nom' => $nomFournisseur],
                            ['statut' => true]
                        );
                        $fournisseurCache[$nomFournisseur] = $f->id;
                    }
                    $fournisseurId = $fournisseurCache[$nomFournisseur];

                    // ── 4e. Capital (montant du paiement) ─────────────────────
                    $capitalRaw = $data['Capital'] ?? '';
                    if ($capitalRaw === '') {
                        throw new \RuntimeException('Capital manquant.');
                    }
                    $montantPaiement = $this->parseAmount($capitalRaw);
                    if ($montantPaiement <= 0) {
                        throw new \RuntimeException("Capital invalide : {$capitalRaw}.");
                    }

                    // ── 4f. Mode de paiement ──────────────────────────────────
                    $modeRaw      = mb_strtolower($data['Mode'] ?? '', 'UTF-8');
                    $modePaiement = self::MODE_MAPPING[$modeRaw] ?? 'virement';

                    // ── 4g. Date Envoi → format MySQL YYYY-MM-DD ──────────────
                    $dateEnvoi = $this->parseDate($data['Date Envoi'] ?? '');
                    if ($dateEnvoi === '') {
                        throw new \RuntimeException('Date Envoi manquante ou invalide.');
                    }

                    // ── 4h. Upsert du paiement via cache (0 SELECT par ligne) ─
                    //    Utilise DB::table() brut pour éviter l'overhead Eloquent.
                    $cacheKey = "{$montantPaiement}|{$clientId}|{$fournisseurId}|{$dateEnvoi}";

                    if (isset($paiementCache[$cacheKey])) {
                        $paiementId = $paiementCache[$cacheKey];
                        DB::table('paiements')
                            ->where('id', $paiementId)
                            ->update(['mode_paiement' => $modePaiement, 'updated_at' => now()]);
                        $misesAJour++;
                    } else {
                        $paiementId = DB::table('paiements')->insertGetId([
                            'client_id'      => $clientId,
                            'fournisseur_id' => $fournisseurId,
                            'montant'        => $montantPaiement,
                            'date_paiement'  => $dateEnvoi,
                            'mode_paiement'  => $modePaiement,
                            'reference'      => Paiement::genererReference(),
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                        $paiementCache[$cacheKey] = $paiementId;
                        $creees++;
                    }

                    // ── 4i. Montant de la répartition ─────────────────────────
                    $montantRepartRaw = $data['Montant'] ?? '';
                    if ($montantRepartRaw === '') {
                        Log::warning("Import ligne {$ligne} : Montant répartition vide, répartition ignorée.", [
                            'paiement_id' => $paiementId,
                        ]);
                        continue;
                    }
                    $montantRepart = $this->parseAmount($montantRepartRaw);

                    // ── 4j. Date Paiement (répartition) ──────────────────────
                    $datePaiementRepart = $this->parseDate($data['Date Paiement'] ?? '');
                    if ($datePaiementRepart === '') {
                        $datePaiementRepart = $dateEnvoi;
                    }

                    // ── 4k. Vérification du plafond via accumulateur en mémoire
                    //    Remplace le SUM() SQL par ligne (était ~10 000 requêtes).
                    /*$dejaReparti = $repartitionTotals[$paiementId] ?? 0.0;
                    if (($dejaReparti + $montantRepart) > $montantPaiement) {
                        throw new \RuntimeException(
                            "Dépassement du plafond : déjà réparti {$dejaReparti} + {$montantRepart} "
                            . "> montant paiement {$montantPaiement}."
                        );
                    }
                    $repartitionTotals[$paiementId] = $dejaReparti + $montantRepart;
                    */
                    // ── 4l. Accumulation dans le tampon d'insertion groupée ───
                    $batchRepartitions[] = [
                        'paiement_id'   => $paiementId,
                        'consultant_id' => $consultantId,
                        'montant'       => $montantRepart,
                        'rib'           => $this->cleanRib($data['RIB'] ?? ''),
                        'banque'        => $this->cleanBanque($data['Banque'] ?? ''),
                        'mode_paiement' => $modePaiement,
                        'date_paiement' => $datePaiementRepart,
                        'remarques'     => ($data['Remarques'] ?? '') ?: null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];

                    // Vider le tampon toutes les BATCH_SIZE lignes
                    if (count($batchRepartitions) >= self::BATCH_SIZE) {
                        Repartition::insert($batchRepartitions);
                        $batchRepartitions = [];
                    }

                } catch (\Exception $e) {
                    $erreurs[] = "Ligne {$ligne} : " . $e->getMessage();
                    Log::error("Import CSV — erreur ligne {$ligne}", [
                        'message' => $e->getMessage(),
                        'data'    => $data ?? $row,
                    ]);
                }
            }

            // Insérer le reliquat du tampon
            if (!empty($batchRepartitions)) {
                Repartition::insert($batchRepartitions);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            Log::error('Import CSV — erreur fatale.', ['message' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }

        fclose($handle);

        // ── 5. Message de résultat ────────────────────────────────────────────
        $total = $creees + $misesAJour;
        $msg   = "Import terminé : {$total} paiement(s) traité(s) ({$creees} créé(s), {$misesAJour} mis à jour).";

        if (!empty($erreurs)) {
            $affichees = array_slice($erreurs, 0, 5);
            $msg .= ' ' . count($erreurs) . ' erreur(s) : ' . implode(' | ', $affichees);
            if (count($erreurs) > 5) {
                $msg .= ' (et ' . (count($erreurs) - 5) . ' autre(s) — voir les logs)';
            }
            Log::warning('Import CSV terminé avec des erreurs.', ['erreurs' => $erreurs]);
        }

        return redirect()->route('paiements.index')->with('success', $msg);
    }

    // =========================================================================
    //  HELPERS PRIVÉS DE NETTOYAGE
    // =========================================================================

    /**
     * Nettoie un en-tête CSV :
     *  - Supprime le BOM UTF-8 et les bytes de contrôle (0x00–0x08, 0x0E–0x1F, 0x7F–0x9F, 0xFE, 0xFF)
     *  - Retire les espaces en début/fin
     */
    private function cleanHeader(string $h): string
    {
        // BOM UTF-8 explicite
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
        // Bytes de contrôle et artefacts d'encodage
        $h = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F\xFE\xFF]/', '', $h);
        return trim($h);
    }

    /**
     * Nettoie une valeur CSV :
     *  - Supprime les bytes de contrôle
     *  - Retire les espaces en début/fin
     *  - Remplace les valeurs vides sémantiques ("None", "0", "-") par une chaîne vide
     */
    private function cleanValue(mixed $v): string
    {
        $v = is_string($v) ? $v : (string) ($v ?? '');
        // Supprimer les artefacts d'encodage
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F\xFE\xFF]/', '', $v);
        $v = trim($v);
        // Valeurs sémantiquement vides
        if (in_array(strtolower($v), ['none', '-', '0'], true)) {
            return '';
        }
        return $v;
    }

    /**
     * Convertit un montant textuel français en float.
     * Exemples : "110 700,00" → 110700.00 / "620,8" → 620.8
     */
    private function parseAmount(string $raw): float
    {
        // Retirer les espaces insécables et les espaces ordinaires utilisés comme séparateurs de milliers
        $raw = preg_replace('/[\x20\xA0\x202F]/', '', $raw);
        // Remplacer la virgule décimale française par un point
        $raw = str_replace(',', '.', $raw);
        // Supprimer tout caractère non numérique sauf le point et le signe moins
        $raw = preg_replace('/[^0-9.\-]/', '', $raw);
        return (float) $raw;
    }

    /**
     * Convertit une date depuis plusieurs formats vers YYYY-MM-DD (standard MySQL).
     * Formats acceptés :
     *   - dd/mm/YYYY   (format français — cas principal du CSV)
     *   - d/m/YYYY     (variante sans zéro de tête)
     *   - YYYY-MM-DD   (déjà au bon format, retourné tel quel)
     */
    private function parseDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }
        // Déjà au format ISO
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        // Format dd/mm/YYYY ou d/m/YYYY (séparateur '/')
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $m)) {
            [$_, $j, $mo, $a] = $m;
            if (checkdate((int) $mo, (int) $j, (int) $a)) {
                return sprintf('%04d-%02d-%02d', (int) $a, (int) $mo, (int) $j);
            }
        }
        // Format dd-mm-YYYY (séparateur '-')
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})$#', $date, $m)) {
            [$_, $j, $mo, $a] = $m;
            if (checkdate((int) $mo, (int) $j, (int) $a)) {
                return sprintf('%04d-%02d-%02d', (int) $a, (int) $mo, (int) $j);
            }
        }
        // Dernier recours : retourner la valeur brute et laisser MySQL/Carbon tenter
        return $date;
    }

    /**
     * Nettoie un numéro RIB : supprime les espaces et normalise.
     */
    private function cleanRib(string $rib): string
    {
        $rib = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/', '', $rib);
        return trim($rib);
    }

    /**
     * Normalise le nom de la banque : supprime les tirets isolés et les valeurs vides.
     */
    private function cleanBanque(string $banque): string
    {
        $banque = trim($banque);
        return ($banque === '-' || $banque === '') ? '' : $banque;
    }
}
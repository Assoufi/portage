<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Mission;
use App\Http\Requests\PaiementRequest;
use App\Services\PaiementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaiementController extends Controller
{
    public function __construct(
        private readonly PaiementService $paiementService
    ) {}

    public function index(Request $request)
    {
        $query = Paiement::with(['client', 'fournisseur', 'mission']);

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
        }

        if ($request->filled('mode_paiement')) {
            $query->parModePaiement($request->mode_paiement);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut === 'actif');
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->parPeriode($request->date_debut, $request->date_fin);
        }

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        $sort = $request->get('sort', 'date_paiement');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $paiements = $query->paginate(15)->withQueryString();
        $clients = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();

        return view('paiements.index', compact('paiements', 'clients', 'fournisseurs'));
    }

    public function create()
    {
        $paiement = new Paiement();
        $clients = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $missions = Mission::with(['client', 'consultant'])->orderBy('date_debut', 'desc')->get();
        $reference = Paiement::genererReference();

        return view('paiements.create', compact('paiement', 'clients', 'fournisseurs', 'missions', 'reference'));
    }

    public function store(PaiementRequest $request)
    {
        try {
            $paiement = $this->paiementService->createPaiement($request->validated());

            return redirect()
                ->route('paiements.show', $paiement)
                ->with('success', 'Paiement créé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
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
        $clients = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $missions = Mission::with(['client', 'consultant'])->orderBy('date_debut', 'desc')->get();

        return view('paiements.edit', compact('paiement', 'clients', 'fournisseurs', 'missions'));
    }

    public function update(PaiementRequest $request, Paiement $paiement)
    {
        try {
            $this->paiementService->updatePaiement($paiement, $request->validated());

            return redirect()
                ->route('paiements.index')
                ->with('success', 'Paiement mis à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du paiement : ' . $e->getMessage());
        }
    }

    public function importer(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $handle = fopen($request->file('fichier')->getRealPath(), 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'Fichier CSV invalide.');
        }

        $creees = 0;
        $misesAJour = 0;
        $erreurs = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $data = array_combine($header, $row);

                $client = Client::firstOrCreate(
                    ['nom' => trim($data['Client'])],
                    ['statut' => true]
                );

                $fournisseur = Fournisseur::firstOrCreate(
                    ['nom' => trim($data['Fournisseur'])],
                    ['statut' => true]
                );

                $montant = str_replace([' ', ','], ['', '.'], trim($data['Capital']));
                $datePaiement = trim($data['Date Paiement']);

                $modeMapping = [
                    'virement' => 'virement',
                    'virement bancaire' => 'virement',
                    'cheque' => 'cheque',
                    'chèque' => 'cheque',
                    'especes' => 'especes',
                    'espèces' => 'especes',
                    'espace' => 'especes',
                ];

                $modeRaw = strtolower(trim($data['Mode'] ?? ''));
                $modePaiement = $modeMapping[$modeRaw] ?? 'virement';

                $paiement = Paiement::where('montant', $montant)
                    ->where('client_id', $client->id)
                    ->where('fournisseur_id', $fournisseur->id)
                    ->where('date_paiement', $datePaiement)
                    ->first();

                $paiementData = [
                    'client_id' => $client->id,
                    'fournisseur_id' => $fournisseur->id,
                    'montant' => $montant,
                    'date_paiement' => $datePaiement,
                    'mode_paiement' => $modePaiement,
                    'remarques' => trim($data['Remarques'] ?? ''),
                ];

                if ($paiement) {
                    $paiement->update($paiementData);
                    $misesAJour++;
                } else {
                    $paiementData['reference'] = Paiement::genererReference();
                    Paiement::create($paiementData);
                    $creees++;
                }
            }

            DB::commit();
            fclose($handle);

            $total = $creees + $misesAJour;

            return redirect()
                ->route('paiements.index')
                ->with('success', "Import terminé : {$total} ligne(s) traitée(s) ({$creees} créée(s), {$misesAJour} mise(s) à jour).");
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    public function destroy(Paiement $paiement)
    {
        try {
            $this->paiementService->deletePaiement($paiement);

            return redirect()
                ->route('paiements.index')
                ->with('success', 'Paiement supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du paiement : ' . $e->getMessage());
        }
    }
}

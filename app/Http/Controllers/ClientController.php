<?php
// app/Http/Controllers/ClientController.php

namespace App\Http\Controllers;

use App\Imports\ClientsImport;
use App\Models\Client;
use App\Http\Requests\ClientRequest;
use App\Services\ClientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $query->recherche($request->search);
        }

        if ($request->filled('statut')) {
            $request->statut == 'actif' ? $query->actif() : $query->inactif();
        }

        if ($request->filled('devise')) {
            $query->parDevise($request->devise);
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $clients = $query->paginate(15)->withQueryString();
        
        $devises = ['MAD', 'EUR', 'USD', 'GBP', 'CAD'];

        return view('clients.index', compact('clients', 'devises'));
    }

    /**
     * Importe les clients depuis un fichier Excel.
     */
    public function importer(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {
            $request->validate([
                'fichier' => 'required|file|mimes:xlsx,xls|max:20480',
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('clients.index')
                ->withErrors($e->errors())
                ->with('error', 'Import Excel : '.($e->errors()['fichier'][0] ?? 'fichier invalide (xlsx/xls, 20 Mo max).'));
        }

        $nom = null;

        try {
            $fichier = $request->file('fichier');
            $nomOriginal = preg_replace('/[\\\\\/:*?"<>|]/', '_', $fichier->getClientOriginalName());
            $nom = now()->format('Ymd_His').'_'.$nomOriginal;

            $enregistre = Storage::disk('local')->putFileAs('imports/clients', $fichier, $nom);

            if ($enregistre === false) {
                throw new \RuntimeException("Impossible d'enregistrer le fichier importé.");
            }

            $chemin = Storage::disk('local')->path('imports/clients/'.$nom);

            $import = new ClientsImport;
            Excel::import($import, $chemin);

            $rapport = app(ClientImportService::class)->importer($import);
            $texte = $this->formaterRapportImport($rapport);
            $success = $rapport['invalides'] === 0;

            return redirect()->route('clients.index')
                ->with('import_rapport', $texte)
                ->with('import_fichier', $nom)
                ->with($success ? 'success' : 'error', $success
                    ? 'Import Excel exécuté - rapport détaillé ci-dessous.'
                    : 'Import Excel terminé avec des erreurs - rapport détaillé ci-dessous.');
        } catch (Throwable $e) {
            return redirect()->route('clients.index')
                ->with('import_rapport', "Rapport d'import des clients\n\nErreur : ".$e->getMessage())
                ->with('import_fichier', $nom)
                ->with('error', 'Import Excel interrompu - voir le rapport ci-dessous.');
        }
    }

    /**
     * Construit le rapport texte affiché après l'import.
     */
    private function formaterRapportImport(array $rapport): string
    {
        $lignes = [
            "Rapport d'import des clients",
            '',
            'Lignes lues : '.$rapport['lignes'],
            'Clients créés : '.$rapport['creees'],
            'Clients mis à jour : '.$rapport['mises_a_jour'],
            'Clients restaurés : '.$rapport['restaurees'],
            'Lignes invalides : '.$rapport['invalides'],
            'Colonnes ignorées ('.$rapport['ignorees'].') : '.(empty($rapport['colonnes_ignorees'])
                ? 'aucune'
                : implode(', ', $rapport['colonnes_ignorees'])),
        ];

        $invalides = array_filter(
            $rapport['details'] ?? [],
            fn (array $detail) => ($detail['statut'] ?? null) === 'invalide'
        );

        $lignes[] = '';

        if ($invalides === []) {
            $lignes[] = 'Aucune ligne invalide.';

            return implode("\n", $lignes);
        }

        $lignes[] = 'Détail des lignes invalides :';

        foreach ($invalides as $detail) {
            $nom = trim((string) ($detail['nom'] ?? ''));

            $lignes[] = '- Ligne '.($detail['ligne'] ?? '?')
                .($nom !== '' ? ' ('.$nom.')' : '')
                .' : '.($detail['message'] ?? 'Ligne invalide.');
        }

        return implode("\n", $lignes);
    }

    public function create()
    {
        $client = new Client();
        $devises = ['MAD', 'EUR', 'USD', 'GBP', 'CAD'];
        
        return view('clients.create', compact('client', 'devises'));
    }

    public function store(ClientRequest $request)
    {
        try {
            DB::beginTransaction();
            
            $client = Client::create($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('clients.index')
                ->with('success', 'Client créé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du client : ' . $e->getMessage());
        }
    }

    public function show(Client $client)
    {
        $client->load(['missions' => function($query) {
            $query->with(['consultant', 'fournisseur'])
                  ->orderBy('date_debut', 'desc')
                  ->limit(10);
        }]);
        
        $stats = [
            'missions_total' => $client->missions()->count(),
            'missions_encours' => $client->missions()->whereNull('date_fin')->count(),
            'missions_terminees' => $client->missions()->whereNotNull('date_fin')->count(),
            'ca_total' => $client->missions()->sum('prix_vente'),
            'factures_total' => $client->factures()->count(),
            'factures_reglees' => $client->factures()->whereNotNull('date_reglement')->count(),
            'factures_impayees' => $client->factures()->whereNull('date_reglement')->count(),
            'factures_montant_total' => $client->factures()->sum('montant_ttc'),
            'factures_montant_regle' => $client->factures()->whereNotNull('date_reglement')->sum('montant_ttc'),
        ];

        $missionsParAnnee = $client->missions()
            ->selectRaw('YEAR(date_debut) as annee, COUNT(*) as nb_missions, SUM(prix_vente) as ca')
            ->groupByRaw('YEAR(date_debut)')
            ->orderBy('annee')
            ->get();

        $facturesParAnnee = $client->factures()
            ->selectRaw('YEAR(date_facture) as annee, COUNT(*) as nb_factures, SUM(montant_ttc) as montant_total')
            ->groupByRaw('YEAR(date_facture)')
            ->orderBy('annee')
            ->get();

        $annees = $missionsParAnnee->pluck('annee')->merge($facturesParAnnee->pluck('annee'))->unique()->sort()->values();

        $evolution = $annees->map(fn($annee) => [
            'annee' => $annee,
            'nb_missions' => $missionsParAnnee->where('annee', $annee)->first()->nb_missions ?? 0,
            'ca' => $missionsParAnnee->where('annee', $annee)->first()->ca ?? 0,
            'nb_factures' => $facturesParAnnee->where('annee', $annee)->first()->nb_factures ?? 0,
            'montant_factures' => $facturesParAnnee->where('annee', $annee)->first()->montant_total ?? 0,
        ]);

        return view('clients.show', compact('client', 'stats', 'evolution'));
    }

    public function edit(Client $client)
    {
        $devises = ['MAD', 'EUR', 'USD', 'GBP', 'CAD'];
        
        return view('clients.edit', compact('client', 'devises'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        try {
            DB::beginTransaction();
            
            $client->update($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('clients.index', $this->filtresCourants($request))
                ->with('success', 'Client mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du client : ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Client $client)
    {
        try {
            if ($client->missions()->exists()) {
                return redirect()
                    ->back()
                    ->with('error', 'Impossible de supprimer ce client car il est lié à des missions.');
            }
            
            DB::beginTransaction();
            
            $client->delete();
            
            DB::commit();
            
            return redirect()
                ->route('clients.index', $this->filtresCourants($request))
                ->with('success', 'Client supprimé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du client : ' . $e->getMessage());
        }
    }

    public function checkIdentification(Request $request)
    {
        $exists = Client::where('num_identification', $request->num_identification)
            ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
            ->exists();
            
        return response()->json(['unique' => !$exists]);
    }

    /**
     * Recherche AJAX pour l'autocomplétion
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $clients = Client::actif()
            ->when($query, fn ($q) => $q->recherche($query))
            ->orderBy('nom')
            ->limit(10)
            ->get(['id', 'nom', 'email', 'devise']);

        return response()->json($clients);
    }
}

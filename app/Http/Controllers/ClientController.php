<?php
// app/Http/Controllers/ClientController.php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'factures_montant_total' => $client->factures()->sum('montant'),
            'factures_montant_regle' => $client->factures()->whereNotNull('date_reglement')->sum('montant'),
        ];

        $missionsParAnnee = $client->missions()
            ->selectRaw('YEAR(date_debut) as annee, COUNT(*) as nb_missions, SUM(prix_vente) as ca')
            ->groupByRaw('YEAR(date_debut)')
            ->orderBy('annee')
            ->get();

        $facturesParAnnee = $client->factures()
            ->selectRaw('YEAR(date_facture) as annee, COUNT(*) as nb_factures, SUM(montant) as montant_total')
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
                ->route('clients.index')
                ->with('success', 'Client mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du client : ' . $e->getMessage());
        }
    }

    public function destroy(Client $client)
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
                ->route('clients.index')
                ->with('success', 'Client supprimé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du client : ' . $e->getMessage());
        }
    }

    public function checkIce(Request $request)
    {
        $exists = Client::where('ice', $request->ice)
            ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
            ->exists();
            
        return response()->json(['unique' => !$exists]);
    }
}
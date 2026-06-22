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
        ];
        
        return view('clients.show', compact('client', 'stats'));
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
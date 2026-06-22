<?php
// app/Http/Controllers/FournisseurController.php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use App\Http\Requests\FournisseurRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FournisseurController extends Controller
{
    public function __construct()
    {
        //$this->authorizeResource(Fournisseur::class, 'fournisseur');
    }

    public function index(Request $request)
    {
        $query = Fournisseur::query();

        if ($request->filled('search')) {
            $query->recherche($request->search);
        }

        if ($request->filled('statut')) {
            $request->statut == 'actif' ? $query->actif() : $query->inactif();
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $fournisseurs = $query->paginate(15)->withQueryString();

        return view('fournisseurs.index', compact('fournisseurs'));
    }

    public function create()
    {
        $fournisseur = new Fournisseur();
        
        return view('fournisseurs.create', compact('fournisseur'));
    }

    public function store(FournisseurRequest $request)
    {
        try {
            DB::beginTransaction();
            
            $fournisseur = Fournisseur::create($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('fournisseurs.index')
                ->with('success', 'Fournisseur créé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du fournisseur : ' . $e->getMessage());
        }
    }

    public function show(Fournisseur $fournisseur)
    {
        $fournisseur->load(['missions' => function($query) {
            $query->with(['consultant', 'client'])
                  ->orderBy('date_debut', 'desc')
                  ->limit(10);
        }]);
        
        $stats = [
            'missions_total' => $fournisseur->missions()->count(),
            'missions_encours' => $fournisseur->missions()->whereNull('date_fin')->count(),
            'missions_terminees' => $fournisseur->missions()->whereNotNull('date_fin')->count(),
            'cout_total' => $fournisseur->missions()->sum(DB::raw('tjm * taux')),
        ];
        
        return view('fournisseurs.show', compact('fournisseur', 'stats'));
    }

    public function edit(Fournisseur $fournisseur)
    {
        return view('fournisseurs.edit', compact('fournisseur'));
    }

    public function update(FournisseurRequest $request, Fournisseur $fournisseur)
    {
        try {
            DB::beginTransaction();
            
            $fournisseur->update($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('fournisseurs.index')
                ->with('success', 'Fournisseur mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du fournisseur : ' . $e->getMessage());
        }
    }

    public function destroy(Fournisseur $fournisseur)
    {
        try {
            if ($fournisseur->missions()->exists()) {
                return redirect()
                    ->back()
                    ->with('error', 'Impossible de supprimer ce fournisseur car il est lié à des missions.');
            }
            
            DB::beginTransaction();
            
            $fournisseur->delete();
            
            DB::commit();
            
            return redirect()
                ->route('fournisseurs.index')
                ->with('success', 'Fournisseur supprimé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du fournisseur : ' . $e->getMessage());
        }
    }
}
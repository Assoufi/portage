<?php
// app/Http/Controllers/ConsultantController.php

namespace App\Http\Controllers;

use App\Models\Consultant;
use App\Http\Requests\ConsultantRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultantController extends Controller
{
    public function __construct()
    {
        
    }

    /**
     * Affiche la liste des consultants
     */
    public function index(Request $request)
    {
        $query = Consultant::query();

        // Filtre de recherche
        if ($request->filled('search')) {
            $query->recherche($request->search);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $request->statut == 'actif' ? $query->actif() : $query->inactif();
        }

        // Tri
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $consultants = $query->paginate(15)->withQueryString();

        return view('consultants.index', compact('consultants'));
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $consultant = new Consultant();
        $modesPaiement = \App\Enums\ModePaiement::cases();
        
        return view('consultants.create', compact('consultant', 'modesPaiement'));
    }

    /**
     * Enregistre un nouveau consultant
     */
    public function store(ConsultantRequest $request)
    {
        try {
            DB::beginTransaction();
            
            $consultant = Consultant::create($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('consultants.index')
                ->with('success', 'Consultant créé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du consultant : ' . $e->getMessage());
        }
    }

    /**
     * Affiche les détails d'un consultant
     */
    public function show(Consultant $consultant)
    {
        $consultant->load(['missions' => function($query) {
            $query->with(['client', 'fournisseur'])
                  ->orderBy('date_debut', 'desc')
                  ->limit(10);
        }]);
        
        $stats = [
            'missions_total' => $consultant->missions()->count(),
            'missions_encours' => $consultant->missions()->whereNull('date_fin')->count(),
            'missions_terminees' => $consultant->missions()->whereNotNull('date_fin')->count(),
            'ca_total' => $consultant->missions()->sum('prix_vente'),
        ];
        
        return view('consultants.show', compact('consultant', 'stats'));
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(Consultant $consultant)
    {
        $modesPaiement = \App\Enums\ModePaiement::cases();
        
        return view('consultants.edit', compact('consultant', 'modesPaiement'));
    }

    /**
     * Met à jour un consultant
     */
    public function update(ConsultantRequest $request, Consultant $consultant)
    {
        try {
            DB::beginTransaction();
            
            $consultant->update($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('consultants.index')
                ->with('success', 'Consultant mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du consultant : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un consultant (soft delete)
     */
    public function destroy(Consultant $consultant)
    {
        try {
            // Vérifier si le consultant a des missions
            if ($consultant->missions()->exists()) {
                return redirect()
                    ->back()
                    ->with('error', 'Impossible de supprimer ce consultant car il est lié à des missions.');
            }
            
            DB::beginTransaction();
            
            $consultant->delete();
            
            DB::commit();
            
            return redirect()
                ->route('consultants.index')
                ->with('success', 'Consultant supprimé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du consultant : ' . $e->getMessage());
        }
    }

    /**
     * Exporte la liste des consultants
     */
    public function export(Request $request)
    {
        $consultants = Consultant::query()
            ->when($request->filled('search'), fn($q) => $q->recherche($request->search))
            ->get();
            
        // Logique d'export CSV/Excel à implémenter
        return response()->json($consultants);
    }
}
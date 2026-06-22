<?php
// app/Http/Controllers/MissionController.php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Consultant;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Http\Requests\MissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MissionController extends Controller
{
    // Pas de constructeur avec authorizeResource pour l'instant
    
    public function index(Request $request)
    {
        $query = Mission::with(['consultant', 'client', 'fournisseur']);

        if ($request->filled('consultant_id')) {
            $query->parConsultant($request->consultant_id);
        }

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('statut')) {
            switch ($request->statut) {
                case 'encours':
                    $query->enCours();
                    break;
                case 'terminees':
                    $query->terminees();
                    break;
            }
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->parPeriode($request->date_debut, $request->date_fin);
        }

        $sort = $request->get('sort', 'date_debut');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $missions = $query->paginate(15)->withQueryString();
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $clients = Client::actif()->orderBy('email')->get();

        return view('missions.index', compact('missions', 'consultants', 'clients'));
    }

    public function create()
    {
        $mission = new Mission();
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $clients = Client::actif()->orderBy('email')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('email')->get();

        return view('missions.create', compact('mission', 'consultants', 'clients', 'fournisseurs'));
    }

    public function store(MissionRequest $request)
    {
        try {
            DB::beginTransaction();
            
            $mission = Mission::create($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('missions.index')
                ->with('success', 'Mission créée avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la mission : ' . $e->getMessage());
        }
    }

    public function show(Mission $mission)
    {
        $mission->load(['consultant', 'client', 'fournisseur']);
        
        $duree = $mission->duree;
        $coutTotal = $mission->tjm * $mission->taux;
        $marge = $mission->prix_vente - $coutTotal;
        $margePourcentage = $coutTotal > 0 ? ($marge / $coutTotal) * 100 : 0;
        
        return view('missions.show', compact('mission', 'duree', 'coutTotal', 'marge', 'margePourcentage'));
    }

    public function edit(Mission $mission)
    {
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $clients = Client::actif()->orderBy('email')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('email')->get();

        return view('missions.edit', compact('mission', 'consultants', 'clients', 'fournisseurs'));
    }

    public function update(MissionRequest $request, Mission $mission)
    {
        try {
            DB::beginTransaction();
            
            $mission->update($request->validated());
            
            DB::commit();
            
            return redirect()
                ->route('missions.index')
                ->with('success', 'Mission mise à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de la mission : ' . $e->getMessage());
        }
    }

    public function destroy(Mission $mission)
    {
        try {
            DB::beginTransaction();
            
            $mission->delete();
            
            DB::commit();
            
            return redirect()
                ->route('missions.index')
                ->with('success', 'Mission supprimée avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression de la mission : ' . $e->getMessage());
        }
    }

    public function calculatePrixVente(Request $request)
    {
        $tjm = floatval($request->tjm);
        $taux = floatval($request->taux);
        $margeSouhaitee = floatval($request->marge_souhaitee ?? 20);
        
        $coutTotal = $tjm * $taux;
        $prixVenteSuggere = $coutTotal * (1 + $margeSouhaitee / 100);
        
        return response()->json([
            'cout_total' => $coutTotal,
            'prix_vente_suggere' => round($prixVenteSuggere, 2),
            'marge' => round($prixVenteSuggere - $coutTotal, 2)
        ]);
    }

    public function dashboard(Request $request)
    {
        $stats = [
            'total_missions' => Mission::count(),
            'missions_encours' => Mission::enCours()->count(),
            'missions_terminees' => Mission::terminees()->count(),
            'ca_total' => Mission::sum('prix_vente'),
            'ca_mois' => Mission::whereMonth('date_debut', Carbon::now()->month)
                ->whereYear('date_debut', Carbon::now()->year)
                ->sum('prix_vente'),
        ];

        $topClients = Client::withCount('missions')
            ->withSum('missions', 'prix_vente')
            ->orderBy('missions_sum_prix_vente', 'desc')
            ->limit(5)
            ->get();

        $topConsultants = Consultant::withCount('missions')
            ->withSum('missions', 'prix_vente')
            ->orderBy('missions_count', 'desc')
            ->limit(5)
            ->get();

        $evolution = Mission::select(
            DB::raw('YEAR(date_debut) as annee'),
            DB::raw('MONTH(date_debut) as mois'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(prix_vente) as ca')
        )
        ->whereYear('date_debut', '>=', Carbon::now()->subYear())
        ->groupBy('annee', 'mois')
        ->orderBy('annee', 'desc')
        ->orderBy('mois', 'desc')
        ->limit(12)
        ->get();

        return view('missions.dashboard', compact('stats', 'topClients', 'topConsultants', 'evolution'));
    }
}
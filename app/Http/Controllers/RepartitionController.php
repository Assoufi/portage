<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Repartition;
use App\Models\Consultant;
use App\Http\Requests\RepartitionRequest;
use App\Services\RepartitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepartitionController extends Controller
{
    public function __construct(
        private readonly RepartitionService $repartitionService
    ) {}

    public function index(Request $request)
    {
        $query = Repartition::with(['paiement.client', 'consultant']);

        if ($request->filled('paiement_id')) {
            $query->parPaiement($request->paiement_id);
        }

        if ($request->filled('consultant_id')) {
            $query->parConsultant($request->consultant_id);
        }

        if ($request->filled('mode_paiement')) {
            $query->parModePaiement($request->mode_paiement);
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

        $repartitions = $query->paginate(15)->withQueryString();
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $paiements = Paiement::actif()->orderBy('reference')->get();

        return view('repartitions.index', compact('repartitions', 'consultants', 'paiements'));
    }

    public function create(Request $request, ?Paiement $paiement = null)
    {
        if (!$paiement && $request->has('paiement_id')) {
            $paiement = Paiement::findOrFail($request->paiement_id);
        }

        $repartition = new Repartition();
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $paiements = Paiement::actif()->orderBy('reference')->get();

        return view('repartitions.create', compact('repartition', 'consultants', 'paiements', 'paiement'));
    }

    public function store(RepartitionRequest $request)
    {
        try {
            $paiement = Paiement::findOrFail($request->paiement_id);
            $repartition = $this->repartitionService->createRepartition($paiement, $request->validated());

            return redirect()
                ->route('paiements.show', $paiement)
                ->with('success', 'Répartition créée avec succès.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la répartition : ' . $e->getMessage());
        }
    }

    public function show(Repartition $repartition)
    {
        $repartition->load(['paiement.client', 'paiement.fournisseur', 'consultant']);
        return view('repartitions.show', compact('repartition'));
    }

    public function edit(Repartition $repartition)
    {
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $paiements = Paiement::actif()->orderBy('reference')->get();

        return view('repartitions.edit', compact('repartition', 'consultants', 'paiements'));
    }

    public function update(RepartitionRequest $request, Repartition $repartition)
    {
        try {
            $this->repartitionService->updateRepartition($repartition, $request->validated());

            return redirect()
                ->route('repartitions.index')
                ->with('success', 'Répartition mise à jour avec succès.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de la répartition : ' . $e->getMessage());
        }
    }

    public function destroy(Repartition $repartition)
    {
        try {
            $this->repartitionService->deleteRepartition($repartition);

            return redirect()
                ->route('repartitions.index')
                ->with('success', 'Répartition supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression de la répartition : ' . $e->getMessage());
        }
    }
}

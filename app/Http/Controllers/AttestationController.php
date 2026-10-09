<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttestationRequest;
use App\Models\Attestation;
use App\Models\Consultant;
use App\Models\Mission;
use App\Services\AttestationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AttestationController extends Controller
{
    public function __construct(
        private readonly AttestationService $attestationService
    ) {}

    public function index(Request $request)
    {
        $query = Attestation::with(['consultant', 'mission']);

        if ($request->filled('consultant_id')) {
            $query->parConsultant($request->consultant_id);
        }

        if ($request->filled('mission_id')) {
            $query->parMission($request->mission_id);
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->parPeriode($request->date_debut, $request->date_fin);
        }

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        $sort = $request->get('sort', 'date_attestation');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $attestations = $query->paginate(15)->withQueryString();
        $consultants = Consultant::actif()->orderBy('nom')->get();
        $missions = Mission::orderBy('titre')->get();
        $stats = $this->attestationService->getStats();

        return view('attestations.index', compact('attestations', 'consultants', 'missions', 'stats'));
    }

    public function create()
    {
        $attestation = new Attestation;
        $consultants = $this->consultantsAvecMissions();
        $missionsData = $this->missionsData();

        return view('attestations.create', compact('attestation', 'consultants', 'missionsData'));
    }

    public function store(AttestationRequest $request)
    {
        try {
            $attestation = $this->attestationService->createAttestation($request->validated());

            return redirect()
                ->route('attestations.show', $attestation)
                ->with('success', 'Attestation créée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de l\'attestation : '.$e->getMessage());
        }
    }

    public function show(Attestation $attestation)
    {
        $attestation->load(['consultant', 'mission']);

        return view('attestations.show', compact('attestation'));
    }

    public function edit(Attestation $attestation)
    {
        $consultants = $this->consultantsAvecMissions($attestation);
        $missionsData = $this->missionsData();

        return view('attestations.edit', compact('attestation', 'consultants', 'missionsData'));
    }

    public function update(AttestationRequest $request, Attestation $attestation)
    {
        try {
            $this->attestationService->updateAttestation($attestation, $request->validated());

            return redirect()
                ->route('attestations.index')
                ->with('success', 'Attestation mise à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de l\'attestation : '.$e->getMessage());
        }
    }

    public function destroy(Attestation $attestation)
    {
        try {
            $this->attestationService->deleteAttestation($attestation);

            return redirect()
                ->route('attestations.index')
                ->with('success', 'Attestation supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression de l\'attestation : '.$e->getMessage());
        }
    }

    public function pdf(Attestation $attestation)
    {
        $attestation->load(['consultant', 'mission.fournisseur']);

        $fournisseur = $attestation->mission?->fournisseur;

        $pdf = Pdf::loadView('attestations.pdf', compact('attestation', 'fournisseur'))
            ->setPaper('a4');

        $nomFichier = 'attestation-mission-'.$attestation->id.'.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Liste des consultants proposés sur les formulaires : seuls ceux
     * qui ont déjà au moins une mission.
     */
    private function consultantsAvecMissions(?Attestation $attestation = null): \Illuminate\Database\Eloquent\Collection
    {
        $consultants = Consultant::actif()
            ->whereHas('missions')
            ->orderBy('nom')
            ->get();

        // En édition, conserver le consultant de l'attestation même s'il
        // n'apparaît pas dans la liste filtrée (données historiques).
        if ($attestation?->consultant_id && ! $consultants->contains('id', $attestation->consultant_id)) {
            $consultants->prepend($attestation->consultant);
        }

        return $consultants;
    }

    /**
     * Missions (avec libellé client et dates) exposées en JSON aux vues
     * pour le filtrage côté client par consultant.
     */
    private function missionsData(): Collection
    {
        return Mission::with('client')->orderBy('titre')->get()
            ->map(fn (Mission $mission) => [
                'id' => $mission->id,
                'consultant_id' => $mission->consultant_id,
                'titre' => $mission->titre ?? 'Mission #'.$mission->id,
                'client' => $mission->client?->nom,
                'date_debut' => $mission->date_debut?->format('Y-m-d'),
                'date_fin' => $mission->date_fin?->format('Y-m-d'),
            ])->values();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\DevisRequest;
use App\Models\Client;
use App\Models\Devis;
use App\Models\Fournisseur;
use App\Models\Mission;
use App\Services\DevisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DevisController extends Controller
{
    public function __construct(
        private readonly DevisService $devisService
    ) {}

    public function index(Request $request)
    {
        $query = Devis::with(['client', 'mission', 'fournisseur']);

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('mission_id')) {
            $query->parMission($request->mission_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->parPeriode($request->date_debut, $request->date_fin);
        }

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        $sort = $request->get('sort', 'date_devis');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $devis = $query->paginate(15)->withQueryString();
        $clients = Client::actif()->orderBy('nom')->get();
        $missions = Mission::orderBy('titre')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $stats = $this->devisService->getStats();

        return view('devis.index', compact('devis', 'clients', 'missions', 'fournisseurs', 'stats'));
    }

    public function create()
    {
        $devis = new Devis;
        $numero = Devis::genererNumeroDevis();

        return view('devis.create', array_merge(
            compact('devis', 'numero'),
            $this->formData()
        ));
    }

    public function store(DevisRequest $request)
    {
        try {
            $devis = $this->devisService->createDevis($request->validated());

            return redirect()
                ->route('devis.show', $devis)
                ->with('success', 'Devis créé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du devis : '.$e->getMessage());
        }
    }

    public function show(Devis $devis)
    {
        $devis->load(['client', 'mission.consultant', 'fournisseur']);

        return view('devis.show', compact('devis'));
    }

    public function edit(Devis $devis)
    {
        $numero = $devis->numero_devis;

        return view('devis.edit', array_merge(
            compact('devis', 'numero'),
            $this->formData()
        ));
    }

    public function update(DevisRequest $request, Devis $devis)
    {
        try {
            $this->devisService->updateDevis($devis, $request->validated());

            return redirect()
                ->route('devis.index')
                ->with('success', 'Devis mis à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du devis : '.$e->getMessage());
        }
    }

    public function destroy(Devis $devis)
    {
        try {
            $this->devisService->deleteDevis($devis);

            return redirect()
                ->route('devis.index')
                ->with('success', 'Devis supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du devis : '.$e->getMessage());
        }
    }

    public function pdf(Devis $devis)
    {
        $devis->load(['client', 'mission.consultant', 'fournisseur']);

        $fournisseur = $devis->fournisseur;

        $pdf = Pdf::loadView('devis.pdf', compact('devis', 'fournisseur'))
            ->setPaper('a4');

        return $pdf->download('devis-'.$devis->numero_devis.'.pdf');
    }

    /**
     * Données communes aux formulaires de création et d'édition.
     */
    private function formData(): array
    {
        return [
            'clients' => Client::actif()->orderBy('nom')->get(),
            'fournisseurs' => Fournisseur::actif()->orderBy('nom')->get(),
            'missions' => Mission::orderBy('titre')->get(),
            'missionsData' => $this->missionsData(),
        ];
    }

    /**
     * Missions exposées en JSON aux vues pour le pré-remplissage côté client
     * (client, fournisseur, prix unitaire) lors de la sélection d'une mission.
     */
    private function missionsData(): Collection
    {
        return Mission::with(['client', 'fournisseur'])->orderBy('titre')->get()
            ->map(fn (Mission $mission) => [
                'id' => $mission->id,
                'titre' => $mission->titre,
                'client_id' => $mission->client_id,
                'fournisseur_id' => $mission->fournisseur_id,
                'prix_vente' => $mission->prix_vente,
            ])->values();
    }
}

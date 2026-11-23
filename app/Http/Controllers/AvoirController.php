<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvoirRequest;
use App\Models\Avoir;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Services\AvoirService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AvoirController extends Controller
{
    public function __construct(
        private readonly AvoirService $avoirService
    ) {}

    public function index(Request $request)
    {
        $query = Avoir::with(['facture', 'client', 'fournisseur', 'consultant']);

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
        }

        if ($request->filled('numero_avoir')) {
            $query->where('numero_avoir', 'LIKE', '%'.$request->numero_avoir.'%');
        }

        if ($request->filled('date_debut')) {
            $query->where('date_avoir', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->where('date_avoir', '<=', $request->date_fin);
        }

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        $sort = $request->get('sort', 'date_avoir');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $avoirs = $query->paginate(15)->withQueryString();
        $clients = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $stats = $this->avoirService->getStats();

        return view('avoirs.index', compact('avoirs', 'clients', 'fournisseurs', 'stats'));
    }

    public function create(Request $request)
    {
        $query = Facture::with(['client', 'fournisseur', 'consultant'])
            ->withSum('avoirs', 'montant_ttc')
            ->orderByDesc('date_facture');

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
        }

        $factures = $query->paginate(10)->withQueryString();

        $factureId = $request->input('facture_id', old('facture_id'));
        $factureSelectionnee = $factureId
            ? Facture::with(['client', 'fournisseur', 'consultant', 'mission', 'details'])->find($factureId)
            : null;

        $avoir = new Avoir;
        $numero = Avoir::genererNumeroAvoir();
        $donnees = $this->donneesDepuisFacture($factureSelectionnee);
        $details = old('details', $factureSelectionnee
            ? $this->detailsDepuisFacture($factureSelectionnee)
            : []);

        $clients = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();

        return view('avoirs.create', compact(
            'avoir',
            'numero',
            'factures',
            'factureSelectionnee',
            'donnees',
            'details',
            'clients',
            'fournisseurs'
        ));
    }

    public function store(AvoirRequest $request)
    {
        try {
            $data = $this->donneesFactureDepuisRequete($request->validated());
            $avoir = $this->avoirService->createAvoir($data);

            return redirect()
                ->route('avoirs.show', $avoir)
                ->with('success', 'Avoir créé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de l\'avoir : '.$e->getMessage());
        }
    }

    public function show(Avoir $avoir)
    {
        $avoir->load(['facture', 'client', 'fournisseur', 'consultant', 'mission', 'details']);

        return view('avoirs.show', compact('avoir'));
    }

    public function edit(Avoir $avoir)
    {
        $avoir->load(['facture.client', 'facture.fournisseur', 'facture.consultant', 'facture.mission', 'details']);

        $donnees = $this->donneesDepuisFacture($avoir->facture, $avoir->id);
        $details = old('details', $avoir->details->map(fn ($d) => [
            'designation' => $d->designation,
            'quantite' => $d->quantite,
            'prix_unitaire' => $d->prix_unitaire,
        ])->values()->all());

        $numero = old('numero_avoir', $avoir->numero_avoir);

        return view('avoirs.edit', compact('avoir', 'donnees', 'details', 'numero'));
    }

    public function update(AvoirRequest $request, Avoir $avoir)
    {
        try {
            $data = $this->donneesFactureDepuisRequete($request->validated());
            $this->avoirService->updateAvoir($avoir, $data);

            return redirect()
                ->route('avoirs.show', $avoir)
                ->with('success', 'Avoir mis à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de l\'avoir : '.$e->getMessage());
        }
    }

    public function destroy(Request $request, Avoir $avoir)
    {
        try {
            $this->avoirService->deleteAvoir($avoir);

            return redirect()
                ->route('avoirs.index', $this->filtresCourants($request))
                ->with('success', 'Avoir supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression de l\'avoir : '.$e->getMessage());
        }
    }

    /**
     * Données JSON d'une facture pour pré-remplir le formulaire d'avoir.
     */
    public function donnees(Facture $facture)
    {
        $facture->load(['client', 'fournisseur', 'consultant', 'mission', 'details']);

        return response()->json($this->donneesDepuisFacture($facture));
    }

    /**
     * Génère le PDF de l'avoir.
     * ?signe=0 produit la version non signée (sans signature/cachet du fournisseur).
     */
    public function pdf(Request $request, Avoir $avoir)
    {
        $avoir->load(['facture', 'client', 'fournisseur', 'consultant', 'mission', 'details']);

        $signe = $request->boolean('signe', true);
        $version = $signe ? 'signe' : 'non-signe';

        $pdf = Pdf::loadView('avoirs.pdf', [
            'avoir' => $avoir,
            'fournisseur' => $avoir->fournisseur,
            'signe' => $signe,
        ])->setPaper('a4');

        return $pdf->stream('avoir-'.$avoir->numero_avoir.'-'.$version.'.pdf');
    }

    /**
     * Complète les données validées avec les acteurs de la facture d'origine.
     * Ces identifiants sont toujours dérivés côté serveur (jamais du formulaire).
     */
    private function donneesFactureDepuisRequete(array $data): array
    {
        $facture = Facture::findOrFail($data['facture_id']);

        return array_merge($data, [
            'fournisseur_id' => $facture->fournisseur_id,
            'client_id' => $facture->client_id,
            'consultant_id' => $facture->consultant_id,
            'mission_id' => $facture->mission_id,
        ]);
    }

    /**
     * Structure commune envoyée à la vue (et en JSON pour l'AJAX).
     */
    private function donneesDepuisFacture(?Facture $facture, ?int $excludeAvoirId = null): array
    {
        if (! $facture) {
            return [
                'facture_id' => null,
                'numero_facture' => null,
                'date_facture' => null,
                'client' => null,
                'fournisseur' => null,
                'consultant' => null,
                'mission' => null,
                'taux_tva' => 20,
                'total_ht' => 0,
                'montant_ttc' => 0,
                'montant_deja_avoire' => 0,
                'montant_restant' => 0,
                'details' => [],
            ];
        }

        $deja = $this->avoirService->montantDejaAvoire($facture, $excludeAvoirId);

        return [
            'facture_id' => $facture->id,
            'numero_facture' => $facture->numero_facture,
            'date_facture' => $facture->date_facture?->format('d/m/Y'),
            'client' => $facture->client?->nom,
            'fournisseur' => $facture->fournisseur?->nom,
            'consultant' => $facture->consultant?->nom,
            'mission' => $facture->mission?->titre,
            'taux_tva' => (float) ($facture->taux_tva ?? 20),
            'total_ht' => (float) ($facture->total_ht ?? 0),
            'montant_ttc' => (float) ($facture->montant_ttc ?? 0),
            'montant_deja_avoire' => $deja,
            'montant_restant' => max(0, (float) ($facture->montant_ttc ?? 0) - $deja),
            'details' => $this->detailsDepuisFacture($facture),
        ];
    }

    private function detailsDepuisFacture(Facture $facture): array
    {
        return $facture->details->map(fn ($d) => [
            'designation' => $d->designation,
            'quantite' => $d->quantite,
            'prix_unitaire' => $d->prix_unitaire,
        ])->values()->all();
    }
}

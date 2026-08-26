<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Consultant;
use App\Http\Requests\FactureRequest;
use App\Services\FactureService;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    public function __construct(
        private readonly FactureService $factureService
    ) {}

    public function index(Request $request)
    {
        $query = Facture::with(['client', 'fournisseur', 'consultant']);

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
        }

        if ($request->filled('consultant_id')) {
            $query->parConsultant($request->consultant_id);
        }

        if ($request->filled('statut')) {
            match ($request->statut) {
                'reglee'    => $query->reglee(),
                'en_attente'=> $query->nonReglee()->actif(),
                'en_retard' => $query->nonReglee()->actif()->where('date_echeance', '<', now()),
                'inactive'  => $query->where('statut', false),
                default     => null,
            };
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->parPeriode($request->date_debut, $request->date_fin);
        }

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        $sort      = $request->get('sort', 'date_facture');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $factures    = $query->paginate(15)->withQueryString();
        $clients     = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $consultants  = Consultant::actif()->orderBy('nom')->get();
        $stats       = $this->factureService->getStats();

        return view('factures.index', compact('factures', 'clients', 'fournisseurs', 'consultants', 'stats'));
    }

    public function create(Request $request)
    {
        $facture     = new Facture();
        $clients     = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $consultants  = Consultant::actif()->orderBy('nom')->get();

        $clone = null;
        $cloneDetails = collect();

        if ($request->filled('clone')) {
            $source = Facture::with('details')->find($request->input('clone'));

            if ($source) {
                $clone = [
                    'fournisseur_id' => $source->fournisseur_id,
                    'client_id'      => $source->client_id,
                    'consultant_id'  => $source->consultant_id,
                    'numero_bcm'     => $source->numero_bcm,
                    'date_facture'   => $source->date_facture?->format('Y-m-d'),
                    'date_echeance'  => $source->date_echeance?->format('Y-m-d'),
                    'date_reception' => $source->date_reception?->format('Y-m-d'),
                    'beneficiaire'   => $source->beneficiaire,
                    'remarques'      => $source->remarques,
                    'tva'            => $source->tva,
                ];

                $cloneDetails = $source->details->map(fn ($d) => [
                    'designation'   => $d->designation,
                    'quantite'      => $d->quantite,
                    'prix_unitaire' => $d->prix_unitaire,
                ])->values();
            }
        }

        // Numéro pré-rempli au format yyyy-ddd : calculé à partir du maximum
        // du fournisseur sélectionné (ou cloné), reste modifiable par l'utilisateur.
        $fournisseurId = (int) ($request->input('fournisseur_id') ?? $clone['fournisseur_id'] ?? 0);
        $numero = Facture::genererNumeroFacturePourFournisseur($fournisseurId ?: null);

        return view('factures.create', compact('facture', 'clients', 'fournisseurs', 'consultants', 'numero', 'clone', 'cloneDetails'));
    }

    /**
     * Endpoint AJAX : renvoie le prochain numéro yyyy-ddd pour un fournisseur donné
     * (appelé lors de la sélection du fournisseur dans le formulaire de création).
     */
    public function numeroSuivant(Request $request)
    {
        $fournisseurId = $request->filled('fournisseur_id') ? (int) $request->input('fournisseur_id') : null;

        return response()->json([
            'numero' => Facture::genererNumeroFacturePourFournisseur($fournisseurId),
        ]);
    }

    public function store(FactureRequest $request)
    {
        try {
            $facture = $this->factureService->createFacture($request->validated());

            return redirect()
                ->route('factures.show', $facture)
                ->with('success', 'Facture créée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la facture : ' . $e->getMessage());
        }
    }

    public function show(Facture $facture)
    {
        $facture->load(['client', 'fournisseur', 'consultant', 'details']);

        return view('factures.show', compact('facture'));
    }

    public function edit(Facture $facture)
    {
        $clients      = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $consultants  = Consultant::actif()->orderBy('nom')->get();

        $detailsJson = $facture->details->map(fn($d) => [
            'designation'  => $d->designation,
            'quantite'     => $d->quantite,
            'prix_unitaire' => $d->prix_unitaire,
            'total_ht'     => $d->total_ht,
        ])->values()->toJson();

        return view('factures.edit', compact('facture', 'clients', 'fournisseurs', 'consultants', 'detailsJson'));
    }

    public function update(FactureRequest $request, Facture $facture)
    {
        try {
            $this->factureService->updateFacture($facture, $request->validated());

            return redirect()
                ->route('factures.index')
                ->with('success', 'Facture mise à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de la facture : ' . $e->getMessage());
        }
    }

    public function destroy(Facture $facture)
    {
        try {
            $this->factureService->deleteFacture($facture);

            return redirect()
                ->route('factures.index')
                ->with('success', 'Facture supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression de la facture : ' . $e->getMessage());
        }
    }

    public function marquerReglee(Request $request, Facture $facture)
    {
        try {
            $validated = $request->validate([
                'date_reglement'      => 'required|date',
                'mode_reglement'      => 'nullable|string|max:50',
                'reference_reglement' => 'nullable|string|max:100',
            ]);

            $this->factureService->marquerReglee($facture, $validated);

            return redirect()
                ->route('factures.show', $facture)
                ->with('success', 'Facture marquée comme réglée.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors du règlement de la facture : ' . $e->getMessage());
        }
    }

    public function cloner(Facture $facture)
    {
        return redirect()->route('factures.create', ['clone' => $facture->id]);
    }

    public function clonerDonnees(Facture $facture)
    {
        return response()->json([
            'success' => true,
            'facture' => [
                'fournisseur_id' => $facture->fournisseur_id,
                'client_id' => $facture->client_id,
                'consultant_id' => $facture->consultant_id,
                'numero_bcm' => $facture->numero_bcm,
                'date_facture' => $facture->date_facture->format('Y-m-d'),
                'date_echeance' => $facture->date_echeance?->format('Y-m-d'),
                'date_reception' => $facture->date_reception?->format('Y-m-d'),
                'beneficiaire' => $facture->beneficiaire,
                'remarques' => $facture->remarques,
                'tva' => $facture->tva,
            ],
            'details' => $facture->details->map(function ($detail) {
                return [
                    'designation' => $detail->designation,
                    'quantite' => $detail->quantite,
                    'prix_unitaire' => $detail->prix_unitaire,
                ];
            })->toArray(),
        ]);
    }
}

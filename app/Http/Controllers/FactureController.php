<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Client;
use App\Models\Fournisseur;
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
        $query = Facture::with(['client', 'fournisseur']);

        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
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
        $stats       = $this->factureService->getStats();

        return view('factures.index', compact('factures', 'clients', 'fournisseurs', 'stats'));
    }

    public function create()
    {
        $facture     = new Facture();
        $clients     = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $numero      = Facture::genererNumeroFacture();

        return view('factures.create', compact('facture', 'clients', 'fournisseurs', 'numero'));
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
        $facture->load(['client', 'fournisseur', 'details']);

        return view('factures.show', compact('facture'));
    }

    public function edit(Facture $facture)
    {
        $clients      = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();

        return view('factures.edit', compact('facture', 'clients', 'fournisseurs'));
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
}

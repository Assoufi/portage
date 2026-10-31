<?php

// app/Http/Controllers/CommandeController.php

namespace App\Http\Controllers;

use App\Http\Requests\CommandeRequest;
use App\Models\Commande;
use App\Models\Mission;
use App\Services\CommandeService;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    public function __construct(
        private readonly CommandeService $commandeService
    ) {}

    public function index(Request $request)
    {
        $query = Commande::with(['mission.fournisseur']);

        if ($request->filled('mission_id')) {
            $query->where('mission_id', $request->mission_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween('date_commande', [$request->date_debut, $request->date_fin]);
        }

        if ($request->filled('recherche')) {
            $recherche = $request->recherche;
            $query->where(function ($q) use ($recherche) {
                $q->where('num_commande', 'like', "%{$recherche}%")
                    ->orWhereHas('mission', fn ($m) => $m->where('titre', 'like', "%{$recherche}%"))
                    ->orWhereHas('mission.fournisseur', fn ($f) => $f->where('nom', 'like', "%{$recherche}%"));
            });
        }

        $sort = in_array($request->get('sort'), ['num_commande', 'date_commande', 'created_at'], true)
            ? $request->get('sort')
            : 'date_commande';
        $direction = in_array(strtolower($request->get('direction')), ['asc', 'desc'], true)
            ? strtolower($request->get('direction'))
            : 'desc';
        $query->orderBy($sort, $direction);

        $commandes = $query->paginate(15)->withQueryString();
        $missions = Mission::enCours()->orderBy('titre')->get();
        $stats = $this->commandeService->getStats();

        return view('commandes.index', compact('commandes', 'missions', 'stats'));
    }

    public function create()
    {
        $commande = new Commande;

        return view('commandes.create', array_merge(
            compact('commande'),
            $this->formData($commande)
        ));
    }
    public function store(CommandeRequest $request)
    {
        try {
            $commande = $this->commandeService->createCommande($request->validated());

            return redirect()
                ->route('commandes.show', $commande)
                ->with('success', 'Commande créée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la commande : '.$e->getMessage());
        }
    }

    public function show(Commande $commande)
    {
        $commande->load(['mission.consultant', 'mission.fournisseur']);

        return view('commandes.show', compact('commande'));
    }

    public function edit(Commande $commande)
    {
        return view('commandes.edit', array_merge(
            compact('commande'),
            $this->formData($commande)
        ));
    }

    public function update(CommandeRequest $request, Commande $commande)
    {
        try {
            $this->commandeService->updateCommande($commande, $request->validated());

            return redirect()
                ->route('commandes.index')
                ->with('success', 'Commande mise à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de la commande : '.$e->getMessage());
        }
    }

    public function destroy(Request $request, Commande $commande)
    {
        try {
            $this->commandeService->deleteCommande($commande);

            return redirect()
                ->route('commandes.index', $request->query())
                ->with('success', 'Commande supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression de la commande : '.$e->getMessage());
        }
    }

    /**
     * Données communes aux formulaires de création et d'édition.
     * Seules les missions en cours sont proposées : le fournisseur et le
     * TJM sont dérivés de la mission sélectionnée. La mission liée à une
     * commande en édition reste présente même si elle n'est plus en cours.
     */
    private function formData(?Commande $commande = null): array
    {
        $missions = Mission::query()
            ->where(function ($q) use ($commande) {
                $q->enCours();

                if ($commande?->mission_id) {
                    $q->orWhere('id', $commande->mission_id);
                }
            })
            ->orderBy('titre')
            ->get();

        return [
            'missions' => $missions,
            // Missions exposées en JSON aux vues pour le pré-remplissage côté client
            // (fournisseur, TJM, période) lors de la sélection d'une mission.
            'missionsData' => $missions
                ->map(fn (Mission $mission) => [
                    'id' => $mission->id,
                    'titre' => $mission->titre,
                    'fournisseur' => $mission->fournisseur?->nom,
                    'tjm' => $mission->tjm,
                ])->values(),
        ];
    }
}

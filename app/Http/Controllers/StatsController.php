<?php
// app/Http/Controllers/StatsController.php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Consultant;
use App\Models\Client;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Mail\DemandeSuiviJours;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatsController extends Controller
{
    /**
     * Affiche les missions non déclarées (missions en cours sans date de fin).
     */
    public function prestationsNonDeclarees(Request $request)
    {
        $query = Mission::with(['consultant', 'client', 'fournisseur'])->enCours();

        // Filtre par consultant
        if ($request->filled('consultant_id')) {
            $query->where('consultant_id', $request->consultant_id);
        }

        // Filtre par client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Recherche par nom/email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('consultant', function ($cq) use ($search) {
                    $cq->where('nom', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('client', function ($clq) use ($search) {
                    $clq->where('nom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Tri par date de début décroissante
        $query->orderBy('date_debut', 'desc');

        $missions = $query->paginate(15)->withQueryString();

        // Données pour les filtres : uniquement les consultants/clients/fournisseurs ayant des missions en cours
        $consultants = Consultant::whereHas('missions', fn($q) => $q->enCours())->orderBy('nom')->get();
        $clients = Client::whereHas('missions', fn($q) => $q->enCours())->orderBy('nom')->get();
        $fournisseurs = Fournisseur::whereHas('missions', fn($q) => $q->enCours())->orderBy('nom')->get();

        return view('stats.prestations-non-declarees', compact('missions', 'consultants', 'clients', 'fournisseurs'));
    }

    /**
     * Exporte les missions non déclarées en CSV (Excel).
     */
    public function exportPrestationsNonDeclarees(Request $request): StreamedResponse
    {
        $missions = Mission::with(['consultant', 'client', 'fournisseur'])
            ->enCours()
            ->when($request->filled('consultant_id'), fn($q) => $q->where('consultant_id', $request->consultant_id))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('fournisseur_id'), fn($q) => $q->where('fournisseur_id', $request->fournisseur_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('consultant', function ($cq) use ($search) {
                        $cq->where('nom', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('client', function ($clq) use ($search) {
                        $clq->where('nom', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('date_debut', 'desc')
            ->get();

        // En-têtes CSV
        $headers = ['Nom', 'Email', 'Client', 'Fournisseur', 'Date Début', 'Date Fin'];

        // Génération du contenu
        $callback = function () use ($headers, $missions) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes
            fputcsv($handle, $headers, ';');

            // Données
            foreach ($missions as $mission) {
                fputcsv($handle, [
                    $mission->consultant->nom ?? '',
                    $mission->consultant->email ?? '',
                    $mission->client->nom ?? '',
                    $mission->fournisseur->nom ?? '',
                    $mission->date_debut?->format('d/m/Y') ?? '',
                    $mission->date_fin?->format('d/m/Y') ?? '',
                ], ';');
            }

            fclose($handle);
        };

        $filename = 'prestations_non_declarees_' . now()->format('Y-m-d_His') . '.csv';

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Envoie un email à chaque consultant ayant des prestations non déclarées.
     */
    public function notifierPrestationsNonDeclarees(Request $request)
    {
        $missions = Mission::with(['consultant', 'client', 'fournisseur'])
            ->enCours()
            ->when($request->filled('consultant_id'), fn($q) => $q->where('consultant_id', $request->consultant_id))
            ->when($request->filled('client_id'), fn($q) => $q->where('client_id', $request->client_id))
            ->when($request->filled('fournisseur_id'), fn($q) => $q->where('fournisseur_id', $request->fournisseur_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($q2) use ($search) {
                    $q2->whereHas('consultant', function ($cq) use ($search) {
                        $cq->where('nom', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('client', function ($clq) use ($search) {
                        $clq->where('nom', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->get();

        // Mois courant en français
        $mois = Carbon::now()->translatedFormat('F Y');

        // Grouper par consultant (dédupliquer)
        $consultants = $missions->pluck('consultant')->unique('id');

        foreach ($consultants as $consultant) {
            Mail::to($consultant->email)
                ->send(new DemandeSuiviJours($consultant->nom, $mois));
        }

        $count = $consultants->count();

        return redirect()
            ->route('stats.prestations-non-declarees', $request->query())
            ->with('success', "{$count} email(s) envoyé(s) avec succès.");
    }
}

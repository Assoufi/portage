<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Consultant;
use App\Models\Mission;
use App\Http\Requests\FactureRequest;
use App\Exports\FacturesExport;
use App\Mail\FactureLivraison;
use App\Services\FactureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FactureController extends Controller
{
    public function __construct(
        private readonly FactureService $factureService
    ) {}

    public function index(Request $request)
    {
        $query = $this->buildIndexQuery($request);

        $factures    = $query->paginate(15)->withQueryString();
        $clientSelectionne = $request->filled('client_id')
            ? Client::find($request->client_id)
            : null;
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $consultantSelectionne = $request->filled('consultant_id')
            ? Consultant::find($request->consultant_id)
            : null;
        $stats       = $this->factureService->getStats();

        return view('factures.index', compact('factures', 'clientSelectionne', 'fournisseurs', 'consultantSelectionne', 'stats'));
    }

    /**
     * Construction de la requête de liste des factures,
     * partagée entre l'affichage (index) et l'export Excel.
     */
    private function buildIndexQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Facture::query();
        if ($request->filled('client_id')) {
            $query->parClient($request->client_id);
        }

        if ($request->filled('fournisseur_id')) {
            $query->parFournisseur($request->fournisseur_id);
        }

        if ($request->filled('consultant_id')) {
            $query->parConsultant($request->consultant_id);
        }

        if ($request->filled('numero_facture')) {
            $query->where('numero_facture', 'LIKE', '%' . $request->numero_facture . '%');
        }

        if ($request->filled('facture_reglee')) {
            if ($request->facture_reglee === 'oui') {
                $query->whereNotNull('mode_reglement')
                    ->where('mode_reglement', '<>', '');
            } elseif ($request->facture_reglee === 'non') {
                $query->where(function ($q) {
                    $q->whereNull('mode_reglement')
                        ->orWhere('mode_reglement', '');
                });
            }
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
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

        if ($request->filled('date_debut')) {
            $query->where('date_facture', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->where('date_facture', '<=', $request->date_fin);
        }

        if ($request->filled('date_reception')) {
            $query->where('date_reception', $request->date_reception);
        }

        if ($request->filled('recherche')) {
            $query->recherche($request->recherche);
        }

        $sort      = $request->get('sort', 'date_facture');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        return $query;
    }

    /**
     * Export Excel des factures (avec les mêmes filtres que la liste).
     */
    public function export(Request $request)
    {
        $query = $this->buildIndexQuery($request);

        return Excel::download(new FacturesExport($query), 'factures-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Import Excel des factures sans N° Facture (délègue à la commande factures:importer).
     */
    public function importer(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {
            $request->validate([
                'fichier' => 'required|file|mimes:xlsx,xls|max:20480',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('factures.index')
                ->withErrors($e->errors())
                ->with('error', 'Import Excel : ' . ($e->errors()['fichier'][0] ?? 'fichier invalide (xlsx/xls, 20 Mo max).'));
        }

        $fichier = $request->file('fichier');
        $nomOriginal = preg_replace('/[\\\\\/:*?"<>|]/', '_', $fichier->getClientOriginalName());
        $nom = now()->format('Ymd_His') . '_' . $nomOriginal;
        $directory = storage_path('app/imports/factures');
        \File::ensureDirectoryExists($directory);
        $fichier->move($directory, $nom);
        $chemin = 'imports/factures/' . $nom;

        try {
            $code = \Artisan::call('factures:importer', ['--file' => $chemin, '--execute' => true]);
            $rapport = \Artisan::output();
        } catch (\Throwable $e) {
            $code = 1;
            $rapport = 'Erreur : ' . $e->getMessage();
        }

        $success = $code === 0;

        return redirect()->route('factures.index')
            ->with('import_rapport', $rapport)
            ->with('import_fichier', $nom)
            ->with($success ? 'success' : 'error', $success
                ? 'Import Excel exécuté — rapport détaillé ci-dessous.'
                : 'Import Excel terminé avec des erreurs — rapport détaillé ci-dessous.');
    }

    public function create(Request $request)
    {
        $facture     = new Facture();
        $clients     = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $consultants  = Consultant::actif()->orderBy('nom')->get();
        $missions     = Mission::orderBy('date_debut', 'desc')->get();

        $clone = null;
        $cloneDetails = collect();

        if ($request->filled('clone')) {
            $source = Facture::with('details')->find($request->input('clone'));

            if ($source) {
                $clone = [
                    'mission_id'     => $source->mission_id,
                    'fournisseur_id' => $source->fournisseur_id,
                    'client_id'      => $source->client_id,
                    'consultant_id'  => $source->consultant_id,
                    'numero_bcm'     => $source->numero_bcm,
                    'date_facture'   => $source->date_facture?->format('Y-m-d'),
                    'date_echeance'  => $source->date_echeance?->format('Y-m-d'),
                    'date_reception' => $source->date_reception?->format('Y-m-d'),
                    'periode'        => $source->periode?->format('Y-m'),
                    'beneficiaire'   => $source->beneficiaire,
                    'remarques'      => $source->remarques,
                    'taux_tva'       => $source->taux_tva,
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

        return view('factures.create', compact('facture', 'clients', 'fournisseurs', 'consultants', 'missions', 'numero', 'clone', 'cloneDetails'));
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

            if ($request->input('action') === 'print') {
                return redirect()->route('factures.pdf', $facture);
            }

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

    /**
     * Génère le PDF de la facture en streaming.
     * ?signe=0 (ou paramètre routeur) produit la version non signée
     * (sans signature/cachet du fournisseur) pour la livraison Papier.
     */
    public function pdf(Request $request, Facture $facture)
    {
        $facture->load(['client', 'fournisseur', 'consultant', 'details']);

        $fournisseur = $facture->fournisseur;
        $signe = $request->boolean('signe', true);

        $pdf = Pdf::loadView('factures.pdf', compact('facture', 'fournisseur', 'signe'))
            ->setPaper('a4');

        return $pdf->stream('facture-'.$facture->numero_facture.'.pdf');
    }

    /**
     * Génère le contenu (octets) du PDF de la facture,
     * selon la version demandée : signée ou non signée.
     */
    private function genererPdf(Facture $facture, bool $signe = true): string
    {
        $facture->loadMissing(['client', 'fournisseur', 'consultant', 'details']);

        return Pdf::loadView('factures.pdf', [
                'facture'     => $facture,
                'fournisseur' => $facture->fournisseur,
                'signe'       => $signe,
            ])
            ->setPaper('a4')
            ->output();
    }

    /**
     * Dispatcher : traite en une fois les factures affichées dans la liste
     * selon le mode_livraison de chaque client :
     *  - "Papier"   : génère le PDF non signé, zippé en un ZIP téléchargeable ;
     *  - "Email"    : envoie le PDF signé en pièce jointe (notifyto / notifycc) ;
     *  - "Whatsapp" : renvoie les liens wa.me à ouvrir côté client.
     */
    public function dispatcher(Request $request)
    {
        $validated = $request->validate([
            'facture_ids'   => 'required|array|max:100',
            'facture_ids.*' => 'integer|exists:factures,id',
        ]);

        set_time_limit(0);

        $this->nettoyerDispatchesAniens();

        $token = now()->format('Ymd_His') . '_' . \Str::lower(\Str::random(8));
        $directory = storage_path('app/dispatches/' . $token);
        \File::ensureDirectoryExists($directory);

        $factures = Facture::with(['client', 'fournisseur', 'consultant', 'details'])
            ->whereIn('id', $validated['facture_ids'])
            ->orderBy('date_facture', 'desc')
            ->get();

        $stats = [
            'papier'      => 0,
            'email'       => 0,
            'whatsapp'    => 0,
            'sans_mode'   => 0,
        ];
        $erreurs   = [];
        $whatsapp  = [];
        $fichiersPapier = []; // [chemin => nom de fichier dans le ZIP]
        $fichiersDejaGeneres = []; // facture_id => chemin (évite les doublons si Papier + Email)

        foreach ($factures as $facture) {
            $client = $facture->client;
            $modes  = collect($client?->mode_livraison ?? []);

            if ($modes->isEmpty()) {
                $stats['sans_mode']++;
                $erreurs[] = [
                    'numero_facture' => $facture->numero_facture,
                    'message'        => 'Aucun mode de livraison défini pour le client '
                        . ($client?->nom ?? '—') . '.',
                ];
                continue;
            }

            foreach ($modes as $mode) {
                try {
                    match ($mode) {
                        'Papier' => $this->dispatcherPapier($facture, $directory, $stats, $fichiersPapier, $fichiersDejaGeneres),
                        'Email'  => $this->dispatcherEmail($facture, $stats, $erreurs),
                        'Whatsapp' => $this->dispatcherWhatsapp($facture, $stats, $whatsapp, $erreurs),
                        default => $erreurs[] = [
                            'numero_facture' => $facture->numero_facture,
                            'message'        => "Mode de livraison inconnu : {$mode}.",
                        ],
                    };
                } catch (\Throwable $e) {
                    \Log::error("Dispatcher facture {$facture->numero_facture} ({$mode}) : " . $e->getMessage());
                    $erreurs[] = [
                        'numero_facture' => $facture->numero_facture,
                        'message'        => "Erreur lors de l'envoi ({$mode}) : " . $e->getMessage(),
                    ];
                }
            }
        }

        $zipToken = null;
        if ($fichiersPapier !== []) {
            $zipToken = 'factures-papier-' . $token . '.zip';
            $this->compresser($directory, $fichiersPapier, storage_path('app/dispatches/' . $zipToken));
        }

        return response()->json([
            'success'  => true,
            'stats'    => $stats,
            'zip_url'  => $zipToken !== null ? route('factures.dispatcher-telecharger', $token) : null,
            'whatsapp' => $whatsapp,
            'erreurs'  => $erreurs,
        ]);
    }

    /**
     * Livraison "Papier" : PDF non signé enregistré pour le ZIP final.
     */
    private function dispatcherPapier(Facture $facture, string $directory, array &$stats, array &$fichiersPapier, array &$fichiersDejaGeneres): void
    {
        if (! isset($fichiersDejaGeneres[$facture->id])) {
            $nom = 'facture-' . $facture->numero_facture . '.pdf';
            $chemin = $directory . DIRECTORY_SEPARATOR . $nom;

            \File::put($chemin, $this->genererPdf($facture, false));

            $fichiersDejaGeneres[$facture->id] = $chemin;
        }

        $fichiersPapier[$fichiersDejaGeneres[$facture->id]] = basename($fichiersDejaGeneres[$facture->id]);
        $stats['papier']++;
    }

    /**
     * Livraison "Email" : envoie la facture signée à notifyto en copie notifycc.
     */
    private function dispatcherEmail(Facture $facture, array &$stats, array &$erreurs): void
    {
        $destinataires = $this->destinatairesPropres($facture->client?->notifyto);
        $copie         = $this->destinatairesPropres($facture->client?->notifycc);

        if ($destinataires === []) {
            $erreurs[] = [
                'numero_facture' => $facture->numero_facture,
                'message'        => 'Mode Email : aucun destinataire (notifyto) pour le client '
                    . ($facture->client?->nom ?? '—') . '.',
            ];
            return;
        }

        $pdf = $this->genererPdf($facture, true);

        \Mail::to($destinataires)
            ->cc($copie)
            ->send(new FactureLivraison($facture, $pdf));

        $stats['email']++;
    }

    /**
     * Livraison "Whatsapp" : prépare le lien wa.me du client.
     */
    private function dispatcherWhatsapp(Facture $facture, array &$stats, array &$whatsapp, array &$erreurs): void
    {
        $telephone = $this->numeroWhatsapp($facture->client?->telephone);

        if ($telephone === null) {
            $erreurs[] = [
                'numero_facture' => $facture->numero_facture,
                'message'        => 'Mode Whatsapp : numéro de téléphone manquant pour le client '
                    . ($facture->client?->nom ?? '—') . '.',
            ];
            return;
        }

        $whatsapp[] = [
            'numero_facture' => $facture->numero_facture,
            'client'         => $facture->client->nom,
            'telephone'      => $telephone,
            'url'            => 'https://wa.me/' . $telephone,
        ];
        $stats['whatsapp']++;
    }

    /**
     * Normalise une liste d'adresses email (séparées par , ou ;) en tableau.
     */
    private function destinatairesPropres(?string $champ): array
    {
        if ($champ === null || $champ === '') {
            return [];
        }

        return collect(preg_split('/[,;]+/', $champ))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Nettoie un numéro de téléphone pour l'API wa.me (chiffres uniquement),
     * en conservant l'indicateur international s'il est présent.
     */
    private function numeroWhatsapp(?string $telephone): ?string
    {
        $numero = preg_replace('/[^0-9]/', '', $telephone ?? '');

        return $numero !== '' ? $numero : null;
    }

    /**
     * Compresse les fichiers Papier générés dans un ZIP.
     */
    private function compresser(string $directory, array $fichiersPapier, string $zipPath): void
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Impossible de créer le fichier ZIP des factures Papier.');
        }

        foreach ($fichiersPapier as $chemin => $nom) {
            if (is_file($chemin)) {
                $zip->addFile($chemin, $nom);
            }
        }

        $zip->close();
        \File::deleteDirectory($directory);
    }

    /**
     * Supprime les livraisons de plus de 24h.
     */
    private function nettoyerDispatchesAniens(): void
    {
        $repertoire = storage_path('app/dispatches');

        if (! is_dir($repertoire)) {
            return;
        }

        foreach (glob($repertoire . DIRECTORY_SEPARATOR . '*') ?: [] as $fichier) {
            if (filemtime($fichier) < now()->subDay()->getTimestamp()) {
                @unlink($fichier);
            }
        }
    }

    /**
     * Téléchargement du ZIP des factures Papier générées par le Dispatcher
     * (renvoie le fichier, supprimé après l'envoi).
     */
    public function dispatcherTelecharger(string $token)
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            abort(404);
        }

        $chemin = storage_path('app/dispatches/factures-papier-' . $token . '.zip');

        if (! is_file($chemin)) {
            abort(404, 'Livrable introuvable (expiré ou déjà téléchargé).');
        }

        return response()->download($chemin, 'factures-papier-' . $token . '.zip')->deleteFileAfterSend(true);
    }

    public function edit(Facture $facture)
    {
        $clients      = Client::actif()->orderBy('nom')->get();
        $fournisseurs = Fournisseur::actif()->orderBy('nom')->get();
        $consultants  = Consultant::actif()->orderBy('nom')->get();
        $missions     = Mission::orderBy('date_debut', 'desc')->get();

        $detailsJson = $facture->details->map(fn($d) => [
            'designation'  => $d->designation,
            'quantite'     => $d->quantite,
            'prix_unitaire' => $d->prix_unitaire,
            'montant_ht'   => $d->montant_ht,
        ])->values()->toJson();

        return view('factures.edit', compact('facture', 'clients', 'fournisseurs', 'consultants', 'missions', 'detailsJson'));
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

    /**
     * Supprime une facture puis redirige vers la liste en préservant
     * les critères de recherche actuels (tous les paramètres de la
     * requête d'origine, sauf le n° de page qui est réinitialisé :
     * la ligne supprimée peut vider la page courante).
     * Le formulaire de suppression de index.blade.php doit donc passer
     * la query string courante dans son action (voir action du form).
     */
    public function destroy(Request $request, Facture $facture)
    {
        try {
            $this->factureService->deleteFacture($facture);

            return redirect()
                ->route('factures.index', $this->filtresCourants($request))
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
                'mission_id' => $facture->mission_id,
                'fournisseur_id' => $facture->fournisseur_id,
                'client_id' => $facture->client_id,
                'consultant_id' => $facture->consultant_id,
                'numero_bcm' => $facture->numero_bcm,
                'date_facture' => $facture->date_facture->format('Y-m-d'),
                'date_echeance' => $facture->date_echeance?->format('Y-m-d'),
                'date_reception' => $facture->date_reception?->format('Y-m-d'),
                'periode' => $facture->periode?->format('Y-m'),
                'beneficiaire' => $facture->beneficiaire,
                'remarques' => $facture->remarques,
                'taux_tva' => $facture->taux_tva,
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\DatabaseScriptRequest;
use App\Models\ExecutedScript;
use App\Services\ScriptExecutionService;
use Illuminate\Http\Request;

/**
 * Scripts d'administration SQL (menu Documents > Database).
 * Accès réservé aux administrateurs (Gate :execute-scripts sur is_admin).
 */
class DatabaseScriptController extends Controller
{
    public function __construct(
        private readonly ScriptExecutionService $scriptService
    ) {}

    public function index()
    {
        $this->authorize('execute-scripts');

        $scripts = ExecutedScript::with('user')
            ->latest()
            ->limit(30)
            ->get();

        return view('database-scripts.index', compact('scripts'));
    }

    /**
     * Réception du fichier : analyse de risques immédiate.
     * - risqué => trace "blocked" + détail des lignes fautives ;
     * - propre => trace "pending" => écran de confirmation.
     */
    public function preview(DatabaseScriptRequest $request)
    {
        $script = $this->scriptService->inspect($request->file('script'), $request->user(), $request->ip());

        if ($script->status === 'blocked') {
            return redirect()->route('database.show', $script)
                ->with('warning', 'Script refusé : requêtes dangereuses détectées.');
        }

        return redirect()->route('database.confirm', $script)
            ->with('status', 'Script validé : confirmez son exécution.');
    }

    /**
     * Écran de double confirmation : checksum ressaisi + engagement de sauvegarde.
     */
    public function confirm(ExecutedScript $script)
    {
        $this->authorize('execute-scripts');

        if ($script->status !== 'pending') {
            return redirect()->route('database.show', $script);
        }

        return view('database-scripts.confirm', compact('script'));
    }

    public function execute(Request $request, ExecutedScript $script)
    {
        $this->authorize('execute-scripts');

        if ($script->status !== 'pending') {
            abort(403, 'Ce script a déjà été traité.');
        }

        if ($script->user_id !== $request->user()->id) {
            abort(403, 'Seul l\'utilisateur qui a téléversé le script peut l\'exécuter.');
        }

        $data = $request->validate([
            'checksum' => ['required', 'string', 'size:64'],
            'backup_confirmed' => ['accepted'],
        ], [
            'checksum.required' => 'La ressaisie du checksum est obligatoire.',
            'checksum.size' => 'Le checksum saisi doit comporter 64 caractères.',
            'backup_confirmed.accepted' => 'Vous devez confirmer disposer d\'une sauvegarde récente.',
        ]);

        if (strtolower(trim($data['checksum'])) !== $script->checksum) {
            return back()->withErrors(['checksum' => 'Le checksum saisi ne correspond pas au fichier validé.']);
        }

        $resultat = $this->scriptService->execute($script);

        if (! $resultat['success']) {
            return redirect()->route('database.show', $script)->with('warning', $resultat['message']);
        }

        return redirect()->route('database.show', $script)->with('status', $resultat['message']);
    }

    public function show(ExecutedScript $script)
    {
        $this->authorize('execute-scripts');

        return view('database-scripts.show', compact('script'));
    }
}

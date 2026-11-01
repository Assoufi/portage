@extends('layouts.app')

@section('title', 'Database — Scripts SQL')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Database — Scripts SQL
        </h2>
    </div>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">
            {{ session('status') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
            {{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-1">Envoyer un script SQL</h3>
            <p class="text-sm text-gray-600 mb-4">
                Fichier <strong>.sql</strong> uniquement, 2 Mo maximum. Toute requête destructrice
                (DROP, ALTER, DELETE, TRUNCATE, RENAME, EXEC, CREATE OR REPLACE...) est bloquée
                pour toujours : ces opérations sont à réaliser via phpMyAdmin / WAMP.
            </p>

            <form method="POST"
                  action="{{ route('database.preview') }}"
                  enctype="multipart/form-data"
                  x-data="{ file: null, error: '' }"
                  x-ref="form"
                  @submit.prevent="
                    error = '';
                    const f = $refs.input.files[0];
                    if (!f) { error = 'Sélectionnez un fichier SQL.'; return; }
                    if (!f.name.toLowerCase().endsWith('.sql')) { error = 'Seuls les fichiers .sql sont acceptés.'; return; }
                    if (f.size > 2 * 1024 * 1024) { error = 'Le fichier dépasse la limite de 2 Mo.'; return; }
                    $refs.form.submit();
                  ">
                @csrf
                <div class="flex items-center gap-3">
                    <input type="file"
                           name="script"
                           accept=".sql"
                           x-ref="input"
                           @change="file = $refs.input.files[0] ? $refs.input.files[0] : null; error = ''"
                           class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-500 file:text-white hover:file:bg-blue-600" />
                    <button type="submit"
                            class="shrink-0 bg-blue-500 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300">
                        Analyser
                    </button>
                </div>
                <p class="mt-2 text-sm text-red-600" x-show="error" x-text="error" style="display:none;"></p>
            </form>
        </div>

        <div class="bg-yellow-50 border border-yellow-300 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="font-semibold text-yellow-800 mb-2">⚠ Procédure obligatoire</h3>
            <ol class="text-sm text-yellow-800 list-decimal pl-4 space-y-1">
                <li>Effectuez une sauvegarde récente de la base (mysqldump / phpMyAdmin).</li>
                <li>Téléversez le script : il est analysé ligne par ligne.</li>
                <li>Un script propre exige la ressaisie de son checksum avant exécution.</li>
                <li>Chaque envoi et exécution est journalisé avec l'IP et la durée.</li>
            </ol>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Historique des scripts</h3>
            <div class="overflow-x-auto">
                @if ($scripts->isEmpty())
                    <p class="text-sm text-gray-500">Aucun script enregistré.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="py-2 pr-4">Fichier</th>
                                <th class="py-2 pr-4">Envoyé par</th>
                                <th class="py-2 pr-4">Statut</th>
                                <th class="py-2 pr-4">Risques</th>
                                <th class="py-2 pr-4">Envoyé le</th>
                                <th class="py-2 pr-4">Exécuté le</th>
                                <th class="py-2 pr-4">Durée</th>
                                <th class="py-2 pr-4">IP</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($scripts as $script)
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-800">{{ $script->filename }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $script->user?->name }}</td>
                                    <td class="py-2 pr-4">{!! $script->statut_badge !!}</td>
                                    <td class="py-2 pr-4 text-gray-600">
                                        @if (is_array($script->detected_risks))
                                            {{ count($script->detected_risks) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $script->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $script->executed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $script->duration_ms !== null ? $script->duration_ms.' ms' : '-' }}</td>
                                    <td class="py-2 pr-4 text-gray-500 font-mono text-xs">{{ $script->ip ?? '-' }}</td>
                                    <td class="py-2 text-right">
                                        <a href="{{ route('database.show', $script) }}"
                                           class="text-blue-600 hover:underline">Détail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Database — Détail du script')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Détail du script
        </h2>
        <a href="{{ route('database.index') }}"
           class="text-sm text-gray-600 hover:text-gray-900">&larr; Retour</a>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto space-y-4">
        @if (session('status'))
            <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">
                {{ session('status') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                {{ session('warning') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ $script->filename }}</h3>
                {!! $script->statut_badge !!}
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-600">Envoyé par</dt>
                    <dd class="font-medium text-gray-900">{{ $script->user?->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Envoyé le</dt>
                    <dd class="font-medium text-gray-900">{{ $script->created_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Exécuté le</dt>
                    <dd class="font-medium text-gray-900">{{ $script->executed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Adresse IP</dt>
                    <dd class="font-mono text-xs text-gray-700">{{ $script->ip ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Durée</dt>
                    <dd class="font-medium text-gray-900">{{ $script->duration_ms !== null ? $script->duration_ms.' ms' : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Lignes affectées</dt>
                    <dd class="font-medium text-gray-900">{{ $script->affected_rows ?? '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-600">Checksum SHA-256</dt>
                    <dd class="mt-1 font-mono text-xs bg-gray-100 rounded p-2 break-all">{{ $script->checksum }}</dd>
                </div>
            </dl>

            @if ($script->status === 'pending')
                <div class="mt-6">
                    <a href="{{ route('database.confirm', $script) }}"
                       class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                        Confirmer l'exécution
                    </a>
                </div>
            @endif
        </div>

        @if ($script->status === 'blocked')
            <div class="bg-red-50 border border-red-300 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-red-800 mb-3">Exécution refusée — requêtes dangereuses détectées</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase text-red-700">
                            <tr>
                                <th class="py-1 pr-4">Règle</th>
                                <th class="py-1 pr-4">Ligne</th>
                                <th class="py-1">Requête en cause</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (collect($script->detected_risks ?? []) as $risque)
                                <tr class="border-t border-red-200">
                                    <td class="py-2 pr-4 font-medium text-red-800 whitespace-nowrap">{{ $risque['rule'] }}</td>
                                    <td class="py-2 pr-4 text-red-800">{{ $risque['line'] }}</td>
                                    <td class="py-2 font-mono text-xs text-gray-800">{{ $risque['excerpt'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-red-700">
                    Les requêtes destructrices sont définitivement bloquées :
                    utilisez phpMyAdmin / WAMP pour ce type d'opération.
                </p>
            </div>
        @endif

        @if ($script->status === 'failed')
            <div class="bg-orange-50 border border-orange-300 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-orange-800 mb-2">Échec d'exécution</h3>
                @if ($script->error_message)
                    <pre class="text-xs text-orange-900 whitespace-pre-wrap">{{ $script->error_message }}</pre>
                @endif
            </div>
        @endif

        @if ($script->status === 'completed')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-2">Sortie d'exécution</h3>
                <pre class="text-xs text-gray-800 bg-gray-50 border border-gray-200 rounded p-3 whitespace-pre-wrap overflow-x-auto">{{ $script->output }}</pre>
            </div>
        @endif
    </div>
@endsection

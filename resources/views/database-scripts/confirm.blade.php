@extends('layouts.app')

@section('title', 'Database — Confirmation')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Confirmer l'exécution
        </h2>
        <a href="{{ route('database.index') }}"
           class="text-sm text-gray-600 hover:text-gray-900">&larr; Retour</a>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
            <strong>Opération sensible.</strong> Le script ciblera la base réelle.
            Vérifiez que vous disposez d'une sauvegarde récente : une exécution
            ne peut pas toujours être annulée (le DDL annule la transaction MySQL).
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 text-sm">
                <div>
                    <dt class="text-gray-600">Fichier</dt>
                    <dd class="font-medium text-gray-900">{{ $script->filename }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600">Envoyé par</dt>
                    <dd class="font-medium text-gray-900">{{ $script->user?->name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-600">Checksum SHA-256 validé</dt>
                    <dd class="mt-1 font-mono text-xs bg-gray-100 rounded p-2 break-all">{{ $script->checksum }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('database.execute', $script) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ressaisissez le checksum SHA-256 du fichier
                    </label>
                    <input type="text"
                           name="checksum"
                           required
                           maxlength="64"
                           autocomplete="off"
                           spellcheck="false"
                           class="w-full font-mono text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="Copiez-collez le checksum ci-dessus" />
                    @error('checksum')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-start gap-2 mb-6">
                    <input type="checkbox"
                           name="backup_confirmed"
                           value="1"
                           class="mt-0.5 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200" />
                    <span class="text-sm text-gray-700">
                        Je confirme disposer d'une sauvegarde récente de la base et
                        j'accepte de lancer ce script en l'état.
                    </span>
                </label>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('database.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">
                        Annuler
                    </a>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg">
                        Exécuter le script
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

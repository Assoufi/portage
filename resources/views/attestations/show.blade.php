{{-- resources/views/attestations/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Détails de l\'Attestation')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Attestation #{{ $attestation->id }}
        </h2>
        <div class="flex space-x-3">
            <a href="{{ route('attestations.pdf', $attestation) }}"
               class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Télécharger le PDF
            </a>
            <a href="{{ route('attestations.edit', $attestation) }}"
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Consultant</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->consultant?->nom ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Mission</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->mission_libelle }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Fonction</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->fonction }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Client</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->client }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date de l'attestation</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->date_attestation_formattee }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Date de signature</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->date_signature_formattee }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Période</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $attestation->date_debut_formattee }} → {{ $attestation->date_fin_formattee ?? 'en cours' }}
                        <span class="text-gray-500">({{ $attestation->duree_formatee }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Créée le</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $attestation->created_at?->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-between">
            <a href="{{ route('attestations.index') }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                ← Retour à la liste
            </a>
            <form action="{{ route('attestations.destroy', $attestation) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette attestation ?')">
                    Supprimer
                </button>
            </form>
        </div>
    </div>
@endsection

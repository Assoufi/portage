@extends('layouts.app')

@section('title', 'Devis '.$devis->numero_devis)

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Devis {{ $devis->numero_devis }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('devis.pdf', $devis) }}" target="_blank"
               class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Imprimer PDF
            </a>
            <a href="{{ route('devis.edit', $devis) }}"
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('devis.index') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Retour
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Informations</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">N° Devis</dt>
                        <dd class="font-mono font-medium text-gray-900">{{ $devis->numero_devis }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Date</dt>
                        <dd class="font-medium text-gray-900">{{ $devis->date_devis_formatee }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Client</dt>
                        <dd class="font-medium text-gray-900">{{ $devis->client->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Fournisseur</dt>
                        <dd class="font-medium text-gray-900">{{ $devis->fournisseur->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Mission</dt>
                        <dd class="font-medium text-gray-900">{{ $devis->mission_libelle }}</dd>
                    </div>
                    @if($devis->mission?->consultant)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Consultant</dt>
                            <dd class="font-medium text-gray-900">{{ $devis->mission->consultant->nom }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Montants ({{ $devis->devise }})</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Quantité</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($devis->quantite, 2, ',', ' ') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Prix unitaire HT</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($devis->prix_unitaire, 2, ',', ' ') }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <dt class="text-gray-500">Total HT</dt>
                        <dd class="font-medium text-gray-900">{{ $devis->total_ht_formate }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">TVA</dt>
                        <dd class="font-medium text-gray-900">{{ $devis->montant_tva_formate }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <dt class="font-semibold text-gray-700">Total TTC</dt>
                        <dd class="font-bold text-gray-900">{{ $devis->montant_ttc_formate }}</dd>
                    </div>
                </dl>
            </div>

            @if($devis->description)
                <div class="md:col-span-2">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Description</h3>
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $devis->description }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection

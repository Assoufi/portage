@extends('layouts.app')

@section('title', 'Détails de la Répartition')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Répartition #{{ $repartition->id }}
        </h2>
        <div class="space-x-2">
            <a href="{{ route('repartitions.edit', $repartition) }}"
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('repartitions.index') }}"
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Retour
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la répartition</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Paiement associé</p>
                            <a href="{{ route('paiements.show', $repartition->paiement) }}"
                               class="text-blue-600 hover:text-blue-900 font-mono font-medium">
                                {{ $repartition->paiement->reference }}
                            </a>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Consultant</p>
                            <p class="font-medium">{{ $repartition->consultant->nom }}</p>
                            <p class="text-sm text-gray-500">{{ $repartition->consultant->email }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Client</p>
                            <p class="font-medium">{{ $repartition->paiement->client->nom }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Fournisseur</p>
                            <p class="font-medium">{{ $repartition->paiement->fournisseur->nom }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Date de paiement</p>
                            <p class="font-medium">{{ $repartition->date_paiement_formatee }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Mode de paiement</p>
                            <p class="font-medium">{{ $repartition->mode_paiement_label }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">RIB</p>
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded font-mono">{{ $repartition->rib ?: 'Non renseigné' }}</code>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Banque</p>
                            <p class="font-medium">{{ $repartition->banque ?: 'Non renseignée' }}</p>
                        </div>

                        @if($repartition->telephone)
                        <div>
                            <p class="text-sm text-gray-600">Téléphone</p>
                            <p class="font-medium">{{ $repartition->telephone }}</p>
                        </div>
                        @endif

                        @if($repartition->remarques)
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Remarques</p>
                            <p class="text-sm">{{ $repartition->remarques }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Montant</h3>

                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-1">Montant de la répartition</p>
                        <p class="text-4xl font-bold text-purple-600">{{ $repartition->montant_formate }}</p>
                    </div>

                    <hr class="my-4">

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Paiement total:</span>
                            <span class="font-medium">{{ $repartition->paiement->montant_formate }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Date de création:</span>
                            <span class="text-sm">{{ $repartition->created_at_formatee }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

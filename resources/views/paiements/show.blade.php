@extends('layouts.app')

@section('title', 'Détails du Paiement')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Paiement {{ $paiement->reference }}
        </h2>
        <div class="space-x-2">
            <a href="{{ route('paiements.edit', $paiement) }}"
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('paiements.index') }}"
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du paiement</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Référence</p>
                            <p class="font-mono font-medium">{{ $paiement->reference }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Statut</p>
                            {!! $paiement->statut_badge !!}
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Client</p>
                            <p class="font-medium">{{ $paiement->client->nom }}</p>
                            <p class="text-sm text-gray-500">{{ $paiement->client->email }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Fournisseur</p>
                            <p class="font-medium">{{ $paiement->fournisseur->nom }}</p>
                        </div>

                        @if($paiement->mission)
                        <div>
                            <p class="text-sm text-gray-600">Mission associée</p>
                            <a href="{{ route('missions.show', $paiement->mission) }}" class="text-blue-600 hover:text-blue-900 font-medium">
                                Mission #{{ $paiement->mission->id }}
                            </a>
                            <p class="text-sm text-gray-500">{{ $paiement->mission->consultant->nom }}</p>
                        </div>
                        @endif

                        <div>
                            <p class="text-sm text-gray-600">Mode de paiement</p>
                            <p class="font-medium">{{ $paiement->mode_paiement_label }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Date de paiement</p>
                            <p class="font-medium">{{ $paiement->date_paiement_formatee }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Date de création</p>
                            <p class="font-medium">{{ $paiement->created_at_formatee }}</p>
                        </div>

                        @if($paiement->remarques)
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Remarques</p>
                            <p class="text-sm">{{ $paiement->remarques }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($paiement->repartitions->count() > 0)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Répartitions</h3>
                        <a href="{{ route('repartitions.create', ['paiement_id' => $paiement->id]) }}"
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition duration-300">
                            + Ajouter
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Consultant</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mode</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($paiement->repartitions as $repartition)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium">{{ $repartition->consultant->nom }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium">
                                            {{ $repartition->montant_formate }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $repartition->mode_paiement_label }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $repartition->date_paiement_formatee }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium">
                                            <a href="{{ route('repartitions.show', $repartition) }}"
                                               class="text-blue-600 hover:text-blue-900 mr-2">Voir</a>
                                            <form action="{{ route('repartitions.destroy', $repartition) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Supprimer cette répartition ?')">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails financiers</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Montant total:</span>
                            <span class="font-bold text-xl">{{ $paiement->montant_formate }}</span>
                        </div>

                        @if($paiement->montant_recu)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Montant reçu:</span>
                            <span class="font-medium text-green-600">{{ $paiement->montant_recu_formate }}</span>
                        </div>

                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600">Solde restant:</span>
                            <span class="font-bold {{ $paiement->solde_restant > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                {{ $paiement->solde_restant_formate }}
                            </span>
                        </div>
                        @endif

                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600">Total réparti:</span>
                            <span class="font-medium {{ $totalReparti > 0 ? 'text-purple-600' : 'text-gray-500' }}">
                                {{ $paiement->total_reparti_formate }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600 font-semibold">Solde disponible:</span>
                            <span class="font-bold {{ $soldeRestant > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($soldeRestant, 2) }}
                            </span>
                        </div>

                        @if($soldeRestant > 0)
                        <div class="pt-4">
                            <a href="{{ route('repartitions.create', ['paiement_id' => $paiement->id]) }}"
                               class="block w-full text-center bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                Répartir le solde
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($paiement->mission)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Mission liée</h3>
                    <p class="text-sm text-gray-600">Client: {{ $paiement->mission->client->nom }}</p>
                    <p class="text-sm text-gray-600">Consultant: {{ $paiement->mission->consultant->nom }}</p>
                    <p class="text-sm text-gray-600">Montant: {{ number_format($paiement->mission->prix_vente, 2) }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

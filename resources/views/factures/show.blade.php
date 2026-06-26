@extends('layouts.app')

@section('title', 'Détails de la Facture')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Facture {{ $facture->numero_facture }}
        </h2>
        <div class="space-x-2">
            <a href="{{ route('factures.edit', $facture) }}"
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('factures.index') }}"
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la facture</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Numéro de facture</p>
                            <p class="font-mono font-medium">{{ $facture->numero_facture }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Statut</p>
                            {!! $facture->statut_badge !!}
                        </div>

                        @if($facture->numero_bcm)
                        <div>
                            <p class="text-sm text-gray-600">Numéro BCM</p>
                            <p class="font-medium">{{ $facture->numero_bcm }}</p>
                        </div>
                        @endif

                        <div>
                            <p class="text-sm text-gray-600">Fournisseur</p>
                            <p class="font-medium">{{ $facture->fournisseur->nom }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Client</p>
                            <p class="font-medium">{{ $facture->client->nom }}</p>
                            <p class="text-sm text-gray-500">{{ $facture->client->email }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Date de facture</p>
                            <p class="font-medium">{{ $facture->date_facture_formatee }}</p>
                        </div>

                        @if($facture->date_echeance)
                        <div>
                            <p class="text-sm text-gray-600">Date d'échéance</p>
                            <p class="font-medium">{{ $facture->date_echeance_formatee }}</p>
                        </div>
                        @endif

                        @if($facture->date_reception)
                        <div>
                            <p class="text-sm text-gray-600">Date de réception</p>
                            <p class="font-medium">{{ $facture->date_reception?->format('d/m/Y') ?? '-' }}</p>
                        </div>
                        @endif

                        @if($facture->beneficiaire)
                        <div>
                            <p class="text-sm text-gray-600">Bénéficiaire</p>
                            <p class="font-medium">{{ $facture->beneficiaire }}</p>
                        </div>
                        @endif

                        <div>
                            <p class="text-sm text-gray-600">Date de création</p>
                            <p class="font-medium">{{ $facture->created_at->format('d/m/Y H:i') }}</p>
                        </div>

                        @if($facture->remarques)
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Remarques</p>
                            <p class="text-sm whitespace-pre-line">{{ $facture->remarques }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($facture->details->count() > 0)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails de la facture</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Désignation</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix unitaire HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">TVA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant TTC</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($facture->details as $detail)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium">{{ $detail->designation }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $detail->quantite }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $detail->prix_unitaire_formate }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $detail->total_ht_formate }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $detail->tva }} %</td>
                                        <td class="px-4 py-3 text-right text-sm font-medium">{{ $detail->montant_ttc_formate }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total TTC</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ $facture->montant_formate }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if($facture->is_reglee && ($facture->mode_reglement || $facture->reference_reglement))
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de règlement</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if($facture->date_reglement)
                        <div>
                            <p class="text-sm text-gray-600">Date de règlement</p>
                            <p class="font-medium">{{ $facture->date_reglement_formatee }}</p>
                        </div>
                        @endif

                        @if($facture->mode_reglement)
                        <div>
                            <p class="text-sm text-gray-600">Mode de règlement</p>
                            <p class="font-medium">{{ $facture->mode_reglement }}</p>
                        </div>
                        @endif

                        @if($facture->reference_reglement)
                        <div>
                            <p class="text-sm text-gray-600">Référence de règlement</p>
                            <p class="font-mono font-medium">{{ $facture->reference_reglement }}</p>
                        </div>
                        @endif
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
                        @if($facture->total_ht)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total HT:</span>
                            <span class="font-medium">{{ $facture->total_ht_formate }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">TVA ({{ $facture->tva }}%):</span>
                            <span class="font-medium">{{ number_format($facture->montant - $facture->total_ht, 2, ',', ' ') }}</span>
                        </div>

                        <div class="border-t pt-2"></div>
                        @endif

                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-semibold">Montant TTC:</span>
                            <span class="font-bold text-xl">{{ $facture->montant_formate }}</span>
                        </div>

                        @if($facture->is_reglee)
                        <div class="pt-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                                <p class="text-green-700 font-semibold">Facture réglée</p>
                                <p class="text-green-600 text-sm">le {{ $facture->date_reglement_formatee }}</p>
                            </div>
                        </div>
                        @else
                        <div class="pt-4">
                            <button onclick="document.getElementById('reglementModal').classList.remove('hidden')"
                                    class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                                Marquer comme réglée
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($facture->date_paiement || $facture->mode_paiement || $facture->reference_paiement)
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Paiement associé</h3>
                    @if($facture->date_paiement)
                        <p class="text-sm text-gray-600">Date: {{ $facture->date_paiement?->format('d/m/Y') ?? '-' }}</p>
                    @endif
                    @if($facture->mode_paiement)
                        <p class="text-sm text-gray-600">Mode: {{ $facture->mode_paiement }}</p>
                    @endif
                    @if($facture->reference_paiement)
                        <p class="text-sm text-gray-600">Réf: {{ $facture->reference_paiement }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <div id="reglementModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Marquer la facture comme réglée</h3>
                <button onclick="document.getElementById('reglementModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('factures.marquer-reglee', $facture) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="date_reglement" class="block text-sm font-medium text-gray-700 mb-2">Date de règlement *</label>
                        <input type="date" name="date_reglement" id="date_reglement" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>
                    <div>
                        <label for="mode_reglement" class="block text-sm font-medium text-gray-700 mb-2">Mode de règlement</label>
                        <select name="mode_reglement" id="mode_reglement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            <option value="">Sélectionner</option>
                            <option value="Virement bancaire">Virement bancaire</option>
                            <option value="Chèque">Chèque</option>
                            <option value="Espèces">Espèces</option>
                            <option value="Carte bancaire">Carte bancaire</option>
                            <option value="Prélèvement">Prélèvement</option>
                        </select>
                    </div>
                    <div>
                        <label for="reference_reglement" class="block text-sm font-medium text-gray-700 mb-2">Référence de règlement</label>
                        <input type="text" name="reference_reglement" id="reference_reglement"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('reglementModal').classList.add('hidden')"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </button>
                    <button type="submit"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Confirmer le règlement
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

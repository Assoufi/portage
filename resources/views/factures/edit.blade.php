@extends('layouts.app')

@section('title', 'Modifier la Facture')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier la facture {{ $facture->numero_facture }}
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('factures.update', $facture) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fournisseur_id" class="block text-sm font-medium text-gray-700 mb-2">Fournisseur *</label>
                        <select name="fournisseur_id" id="fournisseur_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('fournisseur_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id', $facture->fournisseur_id) == $fournisseur->id ? 'selected' : '' }}>
                                    {{ $fournisseur->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('fournisseur_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                        <select name="client_id" id="client_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('client_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $facture->client_id) == $client->id ? 'selected' : '' }} data-tva="{{ $client->tva }}">
                                    {{ $client->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="consultant_id" class="block text-sm font-medium text-gray-700 mb-2">Consultant</label>
                        <select name="consultant_id" id="consultant_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('consultant_id') border-red-500 @enderror">
                            <option value="">Sélectionner un consultant</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}" {{ old('consultant_id', $facture->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultant_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="numero_facture" class="block text-sm font-medium text-gray-700 mb-2">Numéro de facture *</label>
                        <input type="text" name="numero_facture" id="numero_facture"
                               value="{{ old('numero_facture', $facture->numero_facture) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_facture') border-red-500 @enderror"
                               required>
                        @error('numero_facture')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="numero_bcm" class="block text-sm font-medium text-gray-700 mb-2">Numéro BCM</label>
                        <input type="text" name="numero_bcm" id="numero_bcm"
                               value="{{ old('numero_bcm', $facture->numero_bcm) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_bcm') border-red-500 @enderror">
                        @error('numero_bcm')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_facture" class="block text-sm font-medium text-gray-700 mb-2">Date de facture *</label>
                        <input type="date" name="date_facture" id="date_facture"
                               value="{{ old('date_facture', $facture->date_facture->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_facture') border-red-500 @enderror"
                               required>
                        @error('date_facture')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_echeance" class="block text-sm font-medium text-gray-700 mb-2">Date d'échéance</label>
                        <input type="date" name="date_echeance" id="date_echeance"
                               value="{{ old('date_echeance', $facture->date_echeance?->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_echeance') border-red-500 @enderror">
                        @error('date_echeance')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_reception" class="block text-sm font-medium text-gray-700 mb-2">Date de réception</label>
                        <input type="date" name="date_reception" id="date_reception"
                               value="{{ old('date_reception', $facture->date_reception?->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_reception') border-red-500 @enderror">
                        @error('date_reception')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="beneficiaire" class="block text-sm font-medium text-gray-700 mb-2">Bénéficiaire</label>
                        <input type="text" name="beneficiaire" id="beneficiaire"
                               value="{{ old('beneficiaire', $facture->beneficiaire) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('beneficiaire') border-red-500 @enderror">
                        @error('beneficiaire')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">Remarques</label>
                        <textarea name="remarques" id="remarques" rows="4"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques', $facture->remarques) }}</textarea>
                        @error('remarques')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Lignes de facture</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="detailsTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/2">Désignation *</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-1/6">Quantité *</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-1/6">Prix unitaire HT *</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-1/6">Total HT</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-12">Action</th>
                                </tr>
                            </thead>
                            <tbody id="detailsBody">
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total HT</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900" id="totalHtDisplay">0,00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">TVA (<span id="tvaRateDisplay">{{ number_format($facture->tva, 2, ',', ' ') }}</span>%)</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900" id="tvaDisplay">0,00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 border-t">Montant TTC</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 border-t" id="montantDisplay">0,00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-4">
                        <button type="button" id="addDetailBtn"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                            + Ajouter une ligne
                        </button>
                    </div>
                    <input type="hidden" name="tva" id="tvaInput" value="{{ $facture->tva }}">
                    <input type="hidden" name="montant" id="montant" value="{{ $facture->montant }}">
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('factures.index') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <a href="{{ route('factures.index') }}"
                       class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Cloner
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('detailsBody');
            const addBtn = document.getElementById('addDetailBtn');
            const clientSelect = document.getElementById('client_id');
            const tvaInput = document.getElementById('tvaInput');
            const tvaRateDisplay = document.getElementById('tvaRateDisplay');
            const totalHtDisplay = document.getElementById('totalHtDisplay');
            const tvaDisplay = document.getElementById('tvaDisplay');
            const montantDisplay = document.getElementById('montantDisplay');
            let detailIndex = 0;

            const existingDetails = {!! $detailsJson !!};

            function formatNumber(num) {
                return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
            }

            function recalculateTotals() {
                let totalHt = 0;
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const qty = parseFloat(row.querySelector('[name$="[quantite]"]').value) || 0;
                    const prix = parseFloat(row.querySelector('[name$="[prix_unitaire]"]').value) || 0;
                    const total = qty * prix;
                    row.querySelector('[name$="[total_ht]"]').value = total.toFixed(2);
                    row.querySelector('.total-ht-display').textContent = formatNumber(total);
                    totalHt += total;
                });

                const tvaRate = parseFloat(tvaInput.value) || 0;
                const tva = totalHt * tvaRate / 100;
                const montant = totalHt + tva;

                totalHtDisplay.textContent = formatNumber(totalHt);
                tvaDisplay.textContent = formatNumber(tva);
                montantDisplay.textContent = formatNumber(montant);

                document.getElementById('montant').value = montant.toFixed(2);
            }

function addDetailRow(data = {}) {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
                    <td class="px-4 py-3">
                        <textarea name="details[${detailIndex}][designation]"
                               value="${data.designation || ''}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 resize-none"
                               required>${data.designation || ''}</textarea>
                    </td>
                    <td class="px-4 py-3">
                        <input type="number"
                               name="details[${detailIndex}][quantite]"
                               value="${data.quantite || 1}"
                               min="1"
                               step="1"
class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 text-right"
                               required>
                    </td>
                    <td class="px-4 py-3">
                        <input type="number"
                               name="details[${detailIndex}][prix_unitaire]"
                               value="${data.prix_unitaire || ''}"
                               min="0"
                               step="0.01"
class="w-28 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 text-right"
                               required>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <input type="hidden" name="details[${detailIndex}][total_ht]" value="${data.total_ht || 0}">
                        <span class="total-ht-display font-medium">${formatNumber(data.total_ht || 0)}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" class="remove-detail-btn text-red-500 hover:text-red-700" title="Supprimer">
                            <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
                detailIndex++;

                tr.querySelectorAll('input').forEach(input => {
                    input.addEventListener('input', recalculateTotals);
                });
                tr.querySelector('.remove-detail-btn').addEventListener('click', function() {
                    tr.remove();
                    recalculateTotals();
                });

                recalculateTotals();
            }

            addBtn.addEventListener('click', function() {
                addDetailRow();
            });

            clientSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const tva = selectedOption.getAttribute('data-tva');
                if (tva) {
                    tvaInput.value = tva;
                    tvaRateDisplay.textContent = formatNumber(tva);
                    recalculateTotals();
                }
            });

            if (existingDetails.length > 0) {
                existingDetails.forEach(detail => {
                    addDetailRow(detail);
                });
            } else {
                addDetailRow();
            }

            if (clientSelect.value) {
                const selectedOption = clientSelect.options[clientSelect.selectedIndex];
                const tva = selectedOption.getAttribute('data-tva');
                if (tva) {
                    tvaInput.value = tva;
                    tvaRateDisplay.textContent = formatNumber(tva);
                }
            }
        });
    </script>
@endsection
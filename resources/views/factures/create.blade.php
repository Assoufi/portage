@extends('layouts.app')

@section('title', 'Créer une Facture')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Créer une nouvelle facture
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('factures.store') }}">
                @csrf

                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Acteurs</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fournisseur_id" class="block text-sm font-medium text-gray-700 mb-2">Fournisseur *</label>
                        <select name="fournisseur_id" id="fournisseur_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('fournisseur_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id', $clone['fournisseur_id'] ?? '') == $fournisseur->id ? 'selected' : '' }}>
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
                                <option value="{{ $client->id }}" {{ old('client_id', $clone['client_id'] ?? '') == $client->id ? 'selected' : '' }} data-tva="{{ $client->tva }}">
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
                                <option value="{{ $consultant->id }}" {{ old('consultant_id', $clone['consultant_id'] ?? '') == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultant_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mission_id" class="block text-sm font-medium text-gray-700 mb-2">Mission</label>
                        <select name="mission_id" id="mission_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mission_id') border-red-500 @enderror">
                            <option value="">Sélectionner une mission</option>
                            @foreach($missions as $mission)
                                <option value="{{ $mission->id }}" {{ old('mission_id', $clone['mission_id'] ?? '') == $mission->id ? 'selected' : '' }}>
                                    {{ $mission->titre }} — {{ $mission->client->nom ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        @error('mission_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Références & période</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="numero_facture" class="block text-sm font-medium text-gray-700 mb-2">Numéro de facture *</label>
                        <input type="text" name="numero_facture" id="numero_facture"
                               value="{{ old('numero_facture', $numero) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_facture') border-red-500 @enderror"
                               required>
                        @error('numero_facture')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="numero_bcm" class="block text-sm font-medium text-gray-700 mb-2">Numéro BCM</label>
                        <input type="text" name="numero_bcm" id="numero_bcm"
                               value="{{ old('numero_bcm', $clone['numero_bcm'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_bcm') border-red-500 @enderror">
                        @error('numero_bcm')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="periode" class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                        <input type="month" name="periode" id="periode"
                               value="{{ old('periode', $clone['periode'] ?? date('Y-m')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('periode') border-red-500 @enderror">
                        @error('periode')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <select name="statut" id="statut"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('statut') border-red-500 @enderror">
                            <option value="1" {{ old('statut', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('statut', '1') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('statut')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="beneficiaire" class="block text-sm font-medium text-gray-700 mb-2">Bénéficiaire</label>
                        <input type="text" name="beneficiaire" id="beneficiaire"
                               value="{{ old('beneficiaire', $clone['beneficiaire'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('beneficiaire') border-red-500 @enderror">
                        @error('beneficiaire')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Dates</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="date_facture" class="block text-sm font-medium text-gray-700 mb-2">Date de facture *</label>
                        <input type="date" name="date_facture" id="date_facture"
                               value="{{ old('date_facture', $clone['date_facture'] ?? date('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_facture') border-red-500 @enderror"
                               required>
                        @error('date_facture')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_echeance" class="block text-sm font-medium text-gray-700 mb-2">Date d'échéance</label>
                        <input type="date" name="date_echeance" id="date_echeance"
                               value="{{ old('date_echeance', $clone['date_echeance'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_echeance') border-red-500 @enderror">
                        @error('date_echeance')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_reception" class="block text-sm font-medium text-gray-700 mb-2">Date de réception</label>
                        <input type="date" name="date_reception" id="date_reception"
                               value="{{ old('date_reception', $clone['date_reception'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_reception') border-red-500 @enderror">
                        @error('date_reception')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Paiement & règlement</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="date_paiement" class="block text-sm font-medium text-gray-700 mb-2">Date de paiement</label>
                        <input type="date" name="date_paiement" id="date_paiement"
                               value="{{ old('date_paiement', $clone['date_paiement'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_paiement') border-red-500 @enderror">
                        @error('date_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mode_paiement" class="block text-sm font-medium text-gray-700 mb-2">Mode de paiement</label>
                        <select name="mode_paiement" id="mode_paiement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mode_paiement') border-red-500 @enderror">
                            <option value="">Sélectionner</option>
                            @foreach(['Virement bancaire', 'Chèque', 'Espèces', 'Carte bancaire', 'Prélèvement'] as $mode)
                                <option value="{{ $mode }}" {{ old('mode_paiement', $clone['mode_paiement'] ?? '') == $mode ? 'selected' : '' }}>{{ $mode }}</option>
                            @endforeach
                        </select>
                        @error('mode_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_paiement" class="block text-sm font-medium text-gray-700 mb-2">Référence de paiement</label>
                        <input type="text" name="reference_paiement" id="reference_paiement"
                               value="{{ old('reference_paiement', $clone['reference_paiement'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('reference_paiement') border-red-500 @enderror">
                        @error('reference_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_reglement" class="block text-sm font-medium text-gray-700 mb-2">Date de règlement</label>
                        <input type="date" name="date_reglement" id="date_reglement"
                               value="{{ old('date_reglement', $clone['date_reglement'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_reglement') border-red-500 @enderror">
                        @error('date_reglement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mode_reglement" class="block text-sm font-medium text-gray-700 mb-2">Mode de règlement</label>
                        <select name="mode_reglement" id="mode_reglement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mode_reglement') border-red-500 @enderror">
                            <option value="">Sélectionner</option>
                            @foreach(['Virement bancaire', 'Chèque', 'Espèces', 'Carte bancaire', 'Prélèvement'] as $mode)
                                <option value="{{ $mode }}" {{ old('mode_reglement', $clone['mode_reglement'] ?? '') == $mode ? 'selected' : '' }}>{{ $mode }}</option>
                            @endforeach
                        </select>
                        @error('mode_reglement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_reglement" class="block text-sm font-medium text-gray-700 mb-2">Référence de règlement</label>
                        <input type="text" name="reference_reglement" id="reference_reglement"
                               value="{{ old('reference_reglement', $clone['reference_reglement'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('reference_reglement') border-red-500 @enderror">
                        @error('reference_reglement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Remarques</h3>
                <div>
                    <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">Remarques</label>
                    <textarea name="remarques" id="remarques" rows="4"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques', $clone['remarques'] ?? '') }}</textarea>
                    @error('remarques')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
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
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">TVA (<span id="tvaRateDisplay">20,00</span>%)</td>
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
                    <input type="hidden" name="taux_tva" id="tvaInput" value="{{ old('taux_tva', $clone['taux_tva'] ?? '20.00') }}">
                    <input type="hidden" name="montant_ttc" id="montant" value="{{ old('montant_ttc', '0.00') }}">
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('factures.index') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Créer la facture
                    </button>
                    <button type="submit" name="action" value="print"
                            class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Enregistrer et imprimer
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
                    row.querySelector('[name$="[montant_ht]"]').value = total.toFixed(2);
                    row.querySelector('.total-ht-display').textContent = formatNumber(total);
                    totalHt += total;
                });

                const tvaRate = parseFloat(tvaInput.value) || 0;
                const tva = totalHt * tvaRate / 100;
                const montant = totalHt + tva;

                totalHtDisplay.textContent = formatNumber(totalHt);
                tvaDisplay.textContent = formatNumber(tva);
                montantDisplay.textContent = formatNumber(montant);

                const montantInput = document.getElementById('montant');
                if (montantInput) {
                    montantInput.value = montant.toFixed(2);
                }
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
                         @error('details.${detailIndex}.designation')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </td>
                    <td class="px-4 py-3">
                        <input type="number"
                               name="details[${detailIndex}][quantite]"
                               value="${data.quantite || 1}"
                               min="1"
                               step="1"
class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 text-right"
                               required>
                         @error('details.${detailIndex}.quantite')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </td>
                    <td class="px-4 py-3">
                        <input type="number"
                               name="details[${detailIndex}][prix_unitaire]"
                               value="${data.prix_unitaire || ''}"
                               min="0"
                               step="0.01"
class="w-28 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 text-right"
                               required>
                         @error('details.${detailIndex}.prix_unitaire')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </td>
                    <td class="px-4 py-3 text-right">
                        <input type="hidden" name="details[${detailIndex}][montant_ht]" value="${data.montant_ht || 0}">
                        <span class="total-ht-display font-medium">${formatNumber(data.montant_ht || 0)}</span>
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

            const fournisseurSelect = document.getElementById('fournisseur_id');
            const numeroInput = document.getElementById('numero_facture');

            // Récupère le prochain numéro yyyy-ddd du fournisseur sélectionné
            // et pré-remplit le champ (toujours modifiable ensuite par l'utilisateur).
            function fetchNumeroSuivant(fournisseurId) {
                const url = '{{ route('factures.numero-suivant') }}'
                    + (fournisseurId ? '?fournisseur_id=' + encodeURIComponent(fournisseurId) : '');

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.numero) {
                            numeroInput.value = data.numero;
                        }
                    })
                    .catch(() => {});
            }

            fournisseurSelect.addEventListener('change', function() {
                fetchNumeroSuivant(this.value);
            });

            const cloneDetails = @json($cloneDetails ?? []);

            if (clientSelect.value && cloneDetails.length === 0) {
                const selectedOption = clientSelect.options[clientSelect.selectedIndex];
                const tva = selectedOption.getAttribute('data-tva');
                if (tva) {
                    tvaInput.value = tva;
                    tvaRateDisplay.textContent = formatNumber(tva);
                }
            }

            if (cloneDetails.length > 0) {
                cloneDetails.forEach(function(detail) {
                    addDetailRow(detail);
                });
            } else {
                addDetailRow();
            }

            recalculateTotals();
        });
    </script>
@endsection
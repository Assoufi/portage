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
            <form method="POST" action="{{ route('factures.update', $facture) . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}">
                @csrf
                @method('PUT')

                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Acteurs</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="mission_id" class="block text-sm font-medium text-gray-700 mb-2">Mission</label>
                        <select name="mission_id" id="mission_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mission_id') border-red-500 @enderror">
                            <option value="">Sélectionner une mission</option>
                            @foreach($missions as $mission)
                                <option value="{{ $mission->id }}" {{ old('mission_id', $facture->mission_id) == $mission->id ? 'selected' : '' }}
                                        data-fournisseur-id="{{ $mission->fournisseur_id }}"
                                        data-client-id="{{ $mission->client_id }}"
                                        data-consultant-id="{{ $mission->consultant_id }}"
                                        data-client-delai="{{ $mission->client?->delai_paiement ?? '' }}"
                                        data-client-mode="{{ $mission->client?->mode_paiement ?? '' }}">
                                    {{ $mission->titre }} — {{ $mission->client?->nom ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        @error('mission_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

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
                                <option value="{{ $client->id }}" {{ old('client_id', $facture->client_id) == $client->id ? 'selected' : '' }}
                                        data-tva="{{ $client->tva }}"
                                        data-delai="{{ $client->delai_paiement ?? '' }}"
                                        data-mode="{{ $client->mode_paiement ?? '' }}">
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
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Références & période</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                        <label for="periode" class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                        <input type="month" name="periode" id="periode"
                               value="{{ old('periode', $facture->periode?->format('Y-m')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('periode') border-red-500 @enderror">
                        @error('periode')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="statut" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <select name="statut" id="statut"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('statut') border-red-500 @enderror">
                            <option value="1" {{ old('statut', $facture->statut ? '1' : '0') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('statut', $facture->statut ? '1' : '0') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('statut')
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
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Dates</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                </div>

                <h3 class="mt-8 text-lg font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">Paiement & règlement</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="date_paiement" class="block text-sm font-medium text-gray-700 mb-2">Date de paiement</label>
                        <input type="date" name="date_paiement" id="date_paiement"
                               value="{{ old('date_paiement', $facture->date_paiement?->format('Y-m-d')) }}"
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
                                <option value="{{ $mode }}" {{ old('mode_paiement', $facture->mode_paiement) == $mode ? 'selected' : '' }}>{{ $mode }}</option>
                            @endforeach
                        </select>
                        @error('mode_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_paiement" class="block text-sm font-medium text-gray-700 mb-2">Référence de paiement</label>
                        <input type="text" name="reference_paiement" id="reference_paiement"
                               value="{{ old('reference_paiement', $facture->reference_paiement) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('reference_paiement') border-red-500 @enderror">
                        @error('reference_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_reglement" class="block text-sm font-medium text-gray-700 mb-2">Date de règlement</label>
                        <input type="date" name="date_reglement" id="date_reglement"
                               value="{{ old('date_reglement', $facture->date_reglement?->format('Y-m-d')) }}"
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
                                <option value="{{ $mode }}" {{ old('mode_reglement', $facture->mode_reglement) == $mode ? 'selected' : '' }}>{{ $mode }}</option>
                            @endforeach
                        </select>
                        @error('mode_reglement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_reglement" class="block text-sm font-medium text-gray-700 mb-2">Référence de règlement</label>
                        <input type="text" name="reference_reglement" id="reference_reglement"
                               value="{{ old('reference_reglement', $facture->reference_reglement) }}"
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
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques', $facture->remarques) }}</textarea>
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
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">TVA (<span id="tvaRateDisplay">{{ number_format($facture->taux_tva, 2, ',', ' ') }}</span>%)</td>
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
                    <input type="hidden" name="taux_tva" id="tvaInput" value="{{ $facture->taux_tva }}">
                    <input type="hidden" name="montant_ttc" id="montant" value="{{ $facture->montant_ttc }}">
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('factures.index') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <a href="{{ route('factures.index') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}"
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
                // Mode de règlement pré-rempli depuis le mode de paiement du client
                appliquerModeReglement(selectedOption.getAttribute('data-mode'));
                // Échéance recalculée depuis le délai de paiement du client
                updateEcheance();
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

            const missionSelect = document.getElementById('mission_id');
            const fournisseurSelect = document.getElementById('fournisseur_id');
            const consultantSelect = document.getElementById('consultant_id');
            const dateFactureInput = document.getElementById('date_facture');
            const dateEcheanceInput = document.getElementById('date_echeance');
            const modeReglementSelect = document.getElementById('mode_reglement');

            // Correspondance entre le mode de paiement du client (enum)
            // et les libellés du mode de règlement de la facture.
            const modesReglementParClient = {
                virement: 'Virement bancaire',
                cheque: 'Chèque',
                especes: 'Espèces',
            };
            // Classes appliquées aux selects verrouillés par la mission.
            const classesVerrouillees = ['bg-gray-100', 'text-gray-500', 'cursor-not-allowed'];

            // Recalcule la date d'échéance = date de facture + délai de paiement
            // du client sélectionné (fallback 60 jours si délai non renseigné).
            function updateEcheance() {
                if (!dateFactureInput.value) {
                    return;
                }
                const clientOption = clientSelect.options[clientSelect.selectedIndex] ?? null;
                const delai = clientOption ? clientOption.getAttribute('data-delai') : '';
                const jours = (delai !== '' && delai !== null && !isNaN(parseInt(delai, 10))) ? parseInt(delai, 10) : 60;
                const [annee, mois, jour] = dateFactureInput.value.split('-').map(Number);
                const echeance = new Date(annee, mois - 1, jour);
                echeance.setDate(echeance.getDate() + jours);
                const pad = (n) => String(n).padStart(2, '0');
                dateEcheanceInput.value = echeance.getFullYear() + '-' + pad(echeance.getMonth() + 1) + '-' + pad(echeance.getDate());
            }

            // Pré-sélectionne le mode de règlement selon le mode de paiement du client.
            function appliquerModeReglement(mode) {
                const libelle = modesReglementParClient[mode];
                if (libelle && modeReglementSelect) {
                    modeReglementSelect.value = libelle;
                }
            }

            // Crée / met à jour les champs cachés miroir des selects verrouillés
            // (un select disabled n'est pas soumis avec le formulaire).
            function mettreAJourChampsVerrouilles() {
                const form = missionSelect.closest('form');
                ['fournisseur_id', 'client_id', 'consultant_id'].forEach(function(name) {
                    const select = form.querySelector('select[name="' + name + '"]');
                    let input = form.querySelector('input[type="hidden"][name="' + name + '"]');
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        form.appendChild(input);
                    }
                    input.value = select ? select.value : '';
                });
            }

            // Supprime les champs cachés des selects verrouillés.
            function supprimerChampsVerrouilles() {
                const form = missionSelect.closest('form');
                ['fournisseur_id', 'client_id', 'consultant_id'].forEach(function(name) {
                    const input = form.querySelector('input[type="hidden"][name="' + name + '"]');
                    if (input) {
                        input.remove();
                    }
                });
            }

            // Applique la mission sélectionnée :
            //  - avec la mission choisie, sélectionne fournisseur/client/consultant
            //    correspondants, verrouille les trois selects et met à jour
            //    échéance + mode de règlement depuis le client de la mission ;
            //  - sans mission, réactive les trois selects et supprime les champs cachés.
            // Avec `ecrire` à false (chargement où une mission est déjà présente),
            // les valeurs affichées (old()/facture, saisie utilisateur) primant,
            // les selects sont seulement verrouillés sur leur valeur courante.
            function applyMissionSelection(ecrire = false) {
                if (!missionSelect.value) {
                    [fournisseurSelect, clientSelect, consultantSelect].forEach(function(select) {
                        select.disabled = false;
                        classesVerrouillees.forEach(function(cl) { select.classList.remove(cl); });
                    });
                    supprimerChampsVerrouilles();
                    return;
                }

                const option = missionSelect.options[missionSelect.selectedIndex];
                if (ecrire) {
                    if (option.getAttribute('data-fournisseur-id')) {
                        fournisseurSelect.value = option.getAttribute('data-fournisseur-id');
                    }
                    if (option.getAttribute('data-client-id')) {
                        clientSelect.value = option.getAttribute('data-client-id');
                    }
                    if (option.getAttribute('data-consultant-id')) {
                        consultantSelect.value = option.getAttribute('data-consultant-id');
                    }
                    // Échéance et mode de règlement issus du client de la mission
                    updateEcheance();
                    appliquerModeReglement(option.getAttribute('data-client-mode'));
                }

                [fournisseurSelect, clientSelect, consultantSelect].forEach(function(select) {
                    select.disabled = true;
                    classesVerrouillees.forEach(function(cl) { select.classList.add(cl); });
                });
                mettreAJourChampsVerrouilles();
            }

            missionSelect.addEventListener('change', function() {
                // Saisie utilisateur : les valeurs de la mission sont appliquées
                applyMissionSelection(true);
            });

            // Au chargement : si une mission est déjà présente (old()/facture), on
            // verrouille les selects sans réécrire les valeurs affichées, ni
            // l'échéance ni le mode de règlement existants.
            if (missionSelect.value) {
                applyMissionSelection(false);
            }

            // Date de facture modifiée : l'échéance suit automatiquement.
            dateFactureInput.addEventListener('change', updateEcheance);
        });
    </script>
@endsection
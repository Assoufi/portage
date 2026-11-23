@php
    $tauxInitial = old('taux_tva', $donnees['taux_tva'] ?? 20);
    $typeInitial = old('type', $avoir->type ?? 'partiel');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="numero_avoir" class="block text-sm font-medium text-gray-700 mb-2">Numéro d'avoir *</label>
        <input type="text" name="numero_avoir" id="numero_avoir"
               value="{{ old('numero_avoir', $numero) }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_avoir') border-red-500 @enderror"
               required>
        @error('numero_avoir')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="date_avoir" class="block text-sm font-medium text-gray-700 mb-2">Date de l'avoir *</label>
        <input type="date" name="date_avoir" id="date_avoir"
               value="{{ old('date_avoir', $avoir->date_avoir?->format('Y-m-d') ?? date('Y-m-d')) }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_avoir') border-red-500 @enderror"
               required>
        @error('date_avoir')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type d'annulation *</label>
        <select name="type" id="type"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('type') border-red-500 @enderror"
                required>
            <option value="partiel" {{ $typeInitial === 'partiel' ? 'selected' : '' }}>Partielle</option>
            <option value="total" {{ $typeInitial === 'total' ? 'selected' : '' }}>Totale</option>
        </select>
        @error('type')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="taux_tva" class="block text-sm font-medium text-gray-700 mb-2">Taux de TVA (%)</label>
        <input type="number" name="taux_tva" id="taux_tva"
               value="{{ $tauxInitial }}" min="0" max="100" step="0.01"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('taux_tva') border-red-500 @enderror">
        @error('taux_tva')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="motif" class="block text-sm font-medium text-gray-700 mb-2">Motif de l'avoir</label>
        <input type="text" name="motif" id="motif"
               value="{{ old('motif', $avoir->motif) }}"
               placeholder="Ex. remise commerciale, erreur de facturation, prestation non réalisée…"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('motif') border-red-500 @enderror">
        @error('motif')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">Remarques</label>
        <textarea name="remarques" id="remarques" rows="3"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques', $avoir->remarques) }}</textarea>
        @error('remarques')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

<input type="hidden" name="facture_id" id="facture_id" value="{{ old('facture_id', $donnees['facture_id'] ?? '') }}">

<div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Facture annulée</h3>
        <span id="resumeNumero" class="text-sm font-semibold text-gray-900">{{ $donnees['numero_facture'] ?? 'Aucune facture sélectionnée' }}</span>
    </div>
    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
        <div>
            <dt class="text-gray-500">Client</dt>
            <dd id="resumeClient" class="font-medium text-gray-900">{{ $donnees['client'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Fournisseur</dt>
            <dd id="resumeFournisseur" class="font-medium text-gray-900">{{ $donnees['fournisseur'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Consultant</dt>
            <dd id="resumeConsultant" class="font-medium text-gray-900">{{ $donnees['consultant'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Mission</dt>
            <dd id="resumeMission" class="font-medium text-gray-900">{{ $donnees['mission'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Montant facture TTC</dt>
            <dd id="resumeMontant" class="font-medium text-gray-900">{{ number_format($donnees['montant_ttc'] ?? 0, 2, ',', ' ') }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Déjà annulé / restant</dt>
            <dd class="font-medium text-gray-900">
                <span id="resumeDeja">{{ number_format($donnees['montant_deja_avoire'] ?? 0, 2, ',', ' ') }}</span>
                /
                <span id="resumeRestant" class="text-indigo-700">{{ number_format($donnees['montant_restant'] ?? 0, 2, ',', ' ') }}</span>
            </dd>
        </div>
    </dl>
</div>

<div class="mt-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Lignes de l'avoir</h3>
        <button type="button" id="toutAnnulerBtn"
                class="inline-flex items-center gap-2 rounded-md bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 px-4 transition">
            Annuler la facture en totalité
        </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200" id="detailsTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Désignation *</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Quantité *</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-36">Prix unitaire HT *</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Total HT</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16">Action</th>
                </tr>
            </thead>
            <tbody id="detailsBody" class="divide-y divide-gray-100 bg-white"></tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Total HT</td>
                    <td class="px-4 py-2 text-right text-sm font-bold text-gray-900" id="totalHtDisplay">0,00</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-gray-700">TVA (<span id="tvaRateDisplay">0</span>%)</td>
                    <td class="px-4 py-2 text-right text-sm font-bold text-gray-900" id="tvaDisplay">0,00</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-gray-700 border-t">Montant TTC</td>
                    <td class="px-4 py-2 text-right text-sm font-bold text-gray-900 border-t" id="montantDisplay">0,00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <button type="button" id="addDetailBtn"
                class="inline-flex items-center gap-2 rounded-md bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 transition">
            + Ajouter une ligne
        </button>
        <p id="plafondAlerte" class="hidden text-sm font-semibold text-red-600">
            Le montant de l'avoir dépasse le montant restant de la facture.
        </p>
    </div>

    @error('details')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tbody = document.getElementById('detailsBody');
                const addBtn = document.getElementById('addDetailBtn');
                const toutAnnulerBtn = document.getElementById('toutAnnulerBtn');
                const tauxInput = document.getElementById('taux_tva');
                const totalHtDisplay = document.getElementById('totalHtDisplay');
                const tvaDisplay = document.getElementById('tvaDisplay');
                const montantDisplay = document.getElementById('montantDisplay');
                const tvaRateDisplay = document.getElementById('tvaRateDisplay');
                const plafondAlerte = document.getElementById('plafondAlerte');
                let detailIndex = 0;
                let factureDetails = @json($donnees['details'] ?? []);
                let montantRestant = {{ (float) ($donnees['montant_restant'] ?? 0) }};

                function formatNumber(num) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
                }

                function recalculateTotals() {
                    let totalHt = 0;
                    tbody.querySelectorAll('tr').forEach(function (row) {
                        const qty = parseFloat(row.querySelector('[name$="[quantite]"]').value) || 0;
                        const prix = parseFloat(row.querySelector('[name$="[prix_unitaire]"]').value) || 0;
                        const total = qty * prix;
                        row.querySelector('.total-ht-display').textContent = formatNumber(total);
                        totalHt += total;
                    });

                    const taux = parseFloat(tauxInput.value) || 0;
                    const tva = totalHt * taux / 100;
                    const montant = totalHt + tva;

                    totalHtDisplay.textContent = formatNumber(totalHt);
                    tvaDisplay.textContent = formatNumber(tva);
                    montantDisplay.textContent = formatNumber(montant);
                    tvaRateDisplay.textContent = formatNumber(taux);

                    if (montant > montantRestant + 0.01) {
                        plafondAlerte.classList.remove('hidden');
                        montantDisplay.classList.add('text-red-600');
                    } else {
                        plafondAlerte.classList.add('hidden');
                        montantDisplay.classList.remove('text-red-600');
                    }
                }

                function addDetailRow(data) {
                    data = data || {};
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50';
                    tr.innerHTML = `
                        <td class="px-4 py-3">
                            <textarea name="details[${detailIndex}][designation]" rows="1" required
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 resize-none">${data.designation || ''}</textarea>
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" name="details[${detailIndex}][quantite]" value="${data.quantite || 1}"
                                   min="0.01" step="0.01" required
                                   class="w-24 ml-auto block rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 text-right">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" name="details[${detailIndex}][prix_unitaire]" value="${data.prix_unitaire || 0}"
                                   min="0" step="0.01" required
                                   class="w-32 ml-auto block rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 text-right">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="total-ht-display font-medium">0,00</span>
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

                    tr.querySelectorAll('input').forEach(function (input) {
                        input.addEventListener('input', recalculateTotals);
                    });
                    tr.querySelector('.remove-detail-btn').addEventListener('click', function () {
                        tr.remove();
                        recalculateTotals();
                    });

                    recalculateTotals();
                }

                function setDetails(details) {
                    tbody.innerHTML = '';
                    detailIndex = 0;
                    (details && details.length ? details : [{}]).forEach(addDetailRow);
                }

                function applyDonnees(data) {
                    document.getElementById('facture_id').value = data.facture_id || '';
                    document.getElementById('resumeNumero').textContent = data.numero_facture || 'Aucune facture sélectionnée';
                    document.getElementById('resumeClient').textContent = data.client || '—';
                    document.getElementById('resumeFournisseur').textContent = data.fournisseur || '—';
                    document.getElementById('resumeConsultant').textContent = data.consultant || '—';
                    document.getElementById('resumeMission').textContent = data.mission || '—';
                    document.getElementById('resumeMontant').textContent = formatNumber(data.montant_ttc);
                    document.getElementById('resumeDeja').textContent = formatNumber(data.montant_deja_avoire);
                    document.getElementById('resumeRestant').textContent = formatNumber(data.montant_restant);

                    tauxInput.value = data.taux_tva ?? 20;
                    factureDetails = data.details || [];
                    montantRestant = data.montant_restant || 0;
                    setDetails(factureDetails);
                }

                if (addBtn) {
                    addBtn.addEventListener('click', function () { addDetailRow({}); });
                }

                if (toutAnnulerBtn) {
                    toutAnnulerBtn.addEventListener('click', function () {
                        document.getElementById('type').value = 'total';
                        setDetails(factureDetails);
                    });
                }

                if (tauxInput) {
                    tauxInput.addEventListener('input', recalculateTotals);
                }

                window.avoirForm = {
                    applyDonnees: applyDonnees,
                    setDetails: setDetails,
                    recalculateTotals: recalculateTotals,
                };

                setDetails(@json($details));
            });
        </script>
    @endpush
@endonce

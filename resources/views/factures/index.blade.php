@extends('layouts.app')

@section('title', 'Gestion des Factures')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Factures
        </h2>
        <div class="flex gap-2">
            <button id="dispatchBtn" onclick="ouvrirDispatcher()"
                    class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Dispatcher
            </button>
            <button onclick="document.getElementById('importExcelModal').classList.remove('hidden')"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Importer Excel
            </button>
            <a href="{{ route('factures.create') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                + Nouvelle Facture
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('import_rapport'))
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-800">Rapport d'import Excel{{ session('import_fichier') ? ' — ' . session('import_fichier') : '' }}</h3>
                    <button onclick="document.getElementById('importRapport').classList.toggle('hidden')"
                            class="text-xs text-gray-500 hover:text-gray-700">Afficher / masquer</button>
                </div>
                <pre id="importRapport" class="text-xs bg-gray-50 border border-gray-200 rounded-md p-4 overflow-auto max-h-96 whitespace-pre-wrap">{{ session('import_rapport') }}</pre>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">Total factures</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['totalFactures'] }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">Montant total</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['montantTotal'], 2, ',', ' ') }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">En attente</p>
            <p class="text-2xl font-bold text-orange-600">{{ $stats['facturesEnAttente'] }}</p>
        </div>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">En retard</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['facturesEnRetard'] }}</p>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('factures.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Numéro de facture</label>
                    <input type="text" name="numero_facture" value="{{ request('numero_facture') }}"
                           placeholder="FACT-2024-00001…"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Facture réglée</label>
                    <select name="facture_reglee" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        <option value="oui" {{ request('facture_reglee') == 'oui' ? 'selected' : '' }}>Oui</option>
                        <option value="non" {{ request('facture_reglee') == 'non' ? 'selected' : '' }}>Non</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                    <input type="month" name="periode" value="{{ request('periode') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                    <div class="relative">
                        <input type="text" name="client_nom" id="clientSearch"
                               value="{{ request('client_nom', $clientSelectionne?->nom) }}"
                               placeholder="Saisir le nom..."
                               autocomplete="off"
                               data-search-url="{{ url('/api/clients/search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <input type="hidden" name="client_id" id="clientId" value="{{ request('client_id') }}">
                        <div id="clientSuggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fournisseur</label>
                    <select name="fournisseur_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        @foreach($fournisseurs as $fournisseur)
                            <option value="{{ $fournisseur->id }}" {{ request('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                                {{ $fournisseur->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Consultant</label>
                    <div class="relative">
                        <input type="text" name="consultant_nom" id="consultantSearch"
                               value="{{ request('consultant_nom', $consultantSelectionne?->nom) }}"
                               placeholder="Saisir le nom..."
                               autocomplete="off"
                               data-search-url="{{ url('/api/consultants/search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <input type="hidden" name="consultant_id" id="consultantId" value="{{ request('consultant_id') }}">
                        <div id="consultantSuggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="statut" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                        <option value="reglee" {{ request('statut') == 'reglee' ? 'selected' : '' }}>Réglée</option>
                        <option value="en_retard" {{ request('statut') == 'en_retard' ? 'selected' : '' }}>En retard</option>
                        <option value="inactive" {{ request('statut') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date reception</label>
                    <input type="date" name="date_reception" value="{{ request('date_reception') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div class="md:col-span-3 flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Filtrer
                    </button>
                    <button type="submit" formaction="{{ route('factures.export') }}"
                            class="flex-1 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Extraire
                    </button>
                    @if(request()->anyFilled(['numero_facture', 'facture_reglee', 'periode', 'client_id', 'client_nom', 'fournisseur_id', 'consultant_id', 'consultant_nom', 'statut', 'date_debut', 'date_fin', 'date_reception', 'recherche']))
                        <a href="{{ route('factures.index') }}" class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-center transition duration-300">
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Facture
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dates
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Montant
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($factures as $facture)
                        <tr class="hover:bg-gray-50 transition duration-150" data-facture-id="{{ $facture->id }}">
                            {{-- N° + Fournisseur regroupés --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-mono font-medium text-gray-900">{{ $facture->numero_facture }}</div>
                                <div class="text-sm text-gray-500">{{ $facture->fournisseur->nom ?? '—' }}</div>
                            </td>
                            {{-- Client + Consultant regroupés --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $facture->client->nom ?? '—' }}</div>
                                <div class="text-sm text-gray-500">{{ $facture->consultant->nom ?? '—' }}</div>
                            </td>
                            {{-- Date facture → Échéance --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $facture->date_facture_formatee }}
                                    → {{ $facture->date_echeance_formatee }}
                                </div>
                            </td>
                            {{-- Montant TTC + HT regroupés --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-medium text-gray-900">{{ $facture->montant_ttc_formate }}</div>
                                <div class="text-sm text-gray-500">HT: {{ $facture->total_ht_formate }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {!! $facture->statut_badge !!}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('factures.show', $facture) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-800 transition duration-150"
                                       title="Voir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
<a href="{{ route('factures.edit', $facture) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 hover:text-yellow-800 transition duration-150"
                                           title="Modifier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    <a href="{{ route('factures.cloner', $facture) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-50 text-purple-600 hover:bg-purple-100 hover:text-purple-800 transition duration-150"
                                       title="Cloner">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 01-2-2m4 4v6a2 2 0 01-2 2m4-4V4a2 2 0 01-2-2m4 4-6 6m6-6-6 6" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('factures.destroy', $facture) . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-800 transition duration-150"
                                                title="Supprimer"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette facture ?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Aucune facture trouvée.
                                <a href="{{ route('factures.create') }}" class="text-blue-600 hover:text-blue-900 ml-2">Créer une facture</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $factures->withQueryString()->links() }}
        </div>
    </div>

    <div id="importExcelModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Import Excel des factures</h3>
                <button onclick="document.getElementById('importExcelModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('factures.importer') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="var b = this.querySelector('button[type=submit]'); b.disabled = true; b.textContent = 'Import en cours…';">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fichier Excel (.xlsx)</label>
                    <input type="file" name="fichier" accept=".xlsx,.xls" required
                           class="w-full rounded-md border-gray-300 shadow-sm">
                    <p class="mt-2 text-xs text-gray-500">
                        Importe uniquement les lignes dont le <strong>N° Facture</strong> est vide.
                        Colonnes attendues : N° Facture, Date, Désignation, Quantité, Prix unitaire, Montant TTC, Client, Fournisseur, Manager, Devise, Date Paiement BEN, Reference BEN, Paiement Fournisseur, Groupe_Facture, Mois, Contact.
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        Un rapport détaillé (factures créées, référentiels auto-créés, doublons ignorés, écarts TTC) s'affiche en haut de la liste après l'exécution.
                    </p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('importExcelModal').classList.add('hidden')"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </button>
                    <button type="submit"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Importer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal du Dispatcher : confirmation, traitement puis résultats --}}
    <div id="dispatcherModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Dispatcher les factures</h3>
                <button onclick="fermerDispatcher()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div id="dispatcherBody"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initAutocomplete({
            inputId: 'consultantSearch',
            idInputId: 'consultantId',
            boxId: 'consultantSuggestions',
            emptyLabel: 'Aucun consultant trouvé'
        });

        initAutocomplete({
            inputId: 'clientSearch',
            idInputId: 'clientId',
            boxId: 'clientSuggestions',
            emptyLabel: 'Aucun client trouvé'
        });

        function initAutocomplete({ inputId, idInputId, boxId, emptyLabel }) {
            const input = document.getElementById(inputId);
            const idInput = document.getElementById(idInputId);
            const box = document.getElementById(boxId);

            if (!input || !idInput || !box) return;

            let timer;
            let selectedIndex = -1;

            input.addEventListener('input', function () {
                const query = this.value.trim();
                clearTimeout(timer);

                if (query.length < 2) {
                    hide();
                    idInput.value = '';
                    return;
                }

                // Anti-rebond : évite une requête par frappe clavier
                timer = setTimeout(() => fetchItems(query), 300);
            });

            input.addEventListener('keydown', function (e) {
                const items = box.querySelectorAll('.suggestion-item');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    highlight(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    highlight(items);
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    pick(items[selectedIndex]);
                } else if (e.key === 'Escape') {
                    hide();
                }
            });

            document.addEventListener('click', function (e) {
                if (!input.contains(e.target) && !box.contains(e.target)) hide();
            });

            function fetchItems(query) {
                fetch(`${input.dataset.searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(render)
                    .catch(() => showMessage('Erreur lors de la recherche.'));
            }

            function render(items) {
                if (!Array.isArray(items) || items.length === 0) {
                    showMessage(emptyLabel);
                    return;
                }

                box.innerHTML = items.map(c => `
                    <div class="suggestion-item px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                         data-id="${c.id}" data-nom="${c.nom}">
                        <div class="font-medium text-gray-900">${c.nom}</div>
                        <div class="text-xs text-gray-500">${c.email ?? ''}</div>
                    </div>`).join('');

                box.classList.remove('hidden');
                selectedIndex = -1;

                box.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', () => pick(item));
                });
            }

            function showMessage(message) {
                box.innerHTML = `<div class="px-4 py-2 text-gray-500 text-sm">${message}</div>`;
                box.classList.remove('hidden');
                selectedIndex = -1;
            }

            function highlight(items) {
                items.forEach((item, index) => {
                    item.classList.toggle('bg-blue-50', index === selectedIndex);
                    if (index === selectedIndex) item.scrollIntoView({ block: 'nearest' });
                });
            }

            function pick(item) {
                input.value = item.dataset.nom;
                idInput.value = item.dataset.id;
                hide();
            }

            function hide() {
                box.classList.add('hidden');
                selectedIndex = -1;
            }
        }

        /* ===================== Dispatcher ===================== */

        const dispatcherState = { ids: [], busy: false };

        function ouvrirDispatcher() {
            if (dispatcherState.busy) return;

            const ids = Array.from(document.querySelectorAll('tr[data-facture-id]'))
                .map(tr => tr.dataset.factureId);

            if (!ids.length) {
                window.notify?.warning('Aucune facture à dispatcher dans cette liste.');
                return;
            }

            dispatcherState.ids = ids;
            document.getElementById('dispatcherBody').innerHTML = `
                <p class="text-sm text-gray-600 mb-4">
                    Les <b>${ids.length} facture(s) affichée(s)</b> seront traitées selon le mode de livraison
                    de chaque client :
                </p>
                <ul class="text-sm text-gray-600 list-disc pl-5 mb-4 space-y-1">
                    <li><b>Papier</b> : génération d'un PDF non signé, livré en un seul ZIP.</li>
                    <li><b>Email</b> : envoi du PDF signé au destinataire du client (copie en CC).</li>
                    <li><b>Whatsapp</b> : préparation des liens WhatsApp à ouvrir après le traitement.</li>
                </ul>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="fermerDispatcher()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </button>
                    <button type="button" onclick="lancerDispatcher()"
                            class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Confirmer et dispatcher
                    </button>
                </div>`;

            document.getElementById('dispatcherModal').classList.remove('hidden');
        }

        function fermerDispatcher() {
            if (dispatcherState.busy) return;
            document.getElementById('dispatcherModal').classList.add('hidden');
        }

        function lancerDispatcher() {
            if (dispatcherState.busy) return;
            dispatcherState.busy = true;

            document.getElementById('dispatcherBody').innerHTML = `
                <div class="flex flex-col items-center justify-center py-10 text-gray-500">
                    <svg class="animate-spin w-8 h-8 mb-3 text-purple-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" stroke-width="4" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                    <p class="text-sm">Dispatch en cours… (génération des PDF, envois email)</p>
                </div>`;
            document.getElementById('dispatchBtn').disabled = true;

            axios.post('{{ route('factures.dispatcher') }}', { facture_ids: dispatcherState.ids })
                .then(({ data }) => afficherResultatsDispatcher(data))
                .catch(err => {
                    const msg = err?.response?.data?.message
                        || (err?.response?.status === 422 ? 'Vérifications incomplètes, aucune facture traitée.' : 'Erreur lors du dispatch.');
                    window.notify?.error(msg);
                    dispatcherState.busy = false;
                    document.getElementById('dispatchBtn').disabled = false;
                    ouvrirDispatcher();
                });
        }

        function afficherResultatsDispatcher(data) {
            const s = data.stats;

            let html = `
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900">${s.papier ?? 0}</p>
                        <p class="text-xs text-gray-500">PDF Papier (ZIP)</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-600">${s.email ?? 0}</p>
                        <p class="text-xs text-gray-500">Emails envoyés</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-600">${s.whatsapp ?? 0}</p>
                        <p class="text-xs text-gray-500">Liens WhatsApp</p>
                    </div>
                </div>`;

            if (data.zip_url) {
                html += `
                    <a href="${data.zip_url}" download
                       class="block w-full text-center bg-gray-700 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded-lg transition duration-300 mb-4">
                        Télécharger les PDF (ZIP)
                    </a>`;
            }

            if ((data.whatsapp ?? []).length) {
                html += `
                    <p class="text-sm font-medium text-gray-700 mb-2">Livraisons WhatsApp :</p>
                    <ul class="text-sm mb-4 space-y-1 max-h-40 overflow-y-auto">
                        ${data.whatsapp.map(w => `
                            <li class="flex items-center justify-between gap-2">
                                <span class="font-mono text-gray-700">${escapeHtml(w.numero_facture)}</span>
                                <span class="text-gray-500 truncate">${escapeHtml(w.telephone)}</span>
                                <a href="${w.url}" target="_blank" rel="noopener"
                                   class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold py-1 px-3 rounded transition duration-150 whitespace-nowrap">
                                    Ouvrir WhatsApp
                                </a>
                            </li>`).join('')}
                    </ul>
                    <button type="button" onclick="ouvrirToutWhatsapp()"
                            class="text-xs text-green-700 hover:text-green-900 underline mb-4">
                        Tout ouvrir (une fenêtre à la fois)
                    </button>`;
            }

            if ((data.erreurs ?? []).length) {
                html += `
                    <div class="bg-red-50 border border-red-200 rounded-md p-3 mb-4">
                        <p class="text-sm font-medium text-red-700 mb-1">Non livrées / à vérifier :</p>
                        <ul class="text-xs text-red-600 list-disc pl-4 space-y-1">
                            ${data.erreurs.map(e => `<li><span class="font-mono">${escapeHtml(e.numero_facture)}</span> — ${escapeHtml(e.message)}</li>`).join('')}
                        </ul>
                    </div>`;
            }

            if (s.sans_mode) {
                html += `<p class="text-xs text-gray-500 mb-2">${s.sans_mode} facture(s) ignorée(s) (client sans mode de livraison).</p>`;
            }

            html += `
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('dispatcherModal').classList.add('hidden')"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-300">
                        Fermer
                    </button>
                </div>`;

            document.getElementById('dispatcherBody').innerHTML = html;

            if (data.zip_url) {
                const lien = document.createElement('a');
                lien.href = data.zip_url;
                lien.download = '';
                document.body.appendChild(lien);
                lien.click();
                lien.remove();
            }

            if ((data.erreurs ?? []).length) {
                window.notify?.warning('Dispatch terminé, vérifiez les erreurs.');
            } else {
                window.notify?.success('Dispatch terminé avec succès.');
            }
        }

        function ouvrirToutWhatsapp() {
            const liens = Array.from(document.querySelectorAll('#dispatcherBody a[href^="https://wa.me/"]'));
            liens.forEach((lien, i) => setTimeout(() => window.open(lien.href, '_blank'), i * 800));
        }

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            })[c]);
        }

        // Expose les gestionnaires du Dispatcher au scope global,
        // requis par les attributs onclick des boutons et de la modal.
        Object.assign(window, {
            ouvrirDispatcher: ouvrirDispatcher,
            fermerDispatcher: fermerDispatcher,
            lancerDispatcher: lancerDispatcher,
            afficherResultatsDispatcher: afficherResultatsDispatcher,
            ouvrirToutWhatsapp: ouvrirToutWhatsapp,
        });
    });
</script>
@endpush

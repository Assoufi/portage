@extends('layouts.app')

@section('title', 'Gestion des Paiements')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Paiements
        </h2>
        <div class="flex gap-2">
            <button onclick="document.getElementById('importModal').classList.remove('hidden')"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Importer CSV
            </button>
            <a href="{{ route('paiements.create') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                + Nouveau Paiement
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form id="search-form" method="GET" action="{{ route('paiements.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Consultant</label>
                    <input type="text" name="consultant" value="{{ request('consultant') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="Rechercher un consultant...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capital</label>
                    <input type="text" name="capital" value="{{ request('capital') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="Montant...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                    <input type="text" name="client" value="{{ request('client') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="Rechercher un client...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fournisseur</label>
                    <input type="text" name="fournisseur" value="{{ request('fournisseur') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="Rechercher un fournisseur...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Envoi</label>
                    <input type="date" name="date_envoi" value="{{ request('date_envoi') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Paiement</label>
                    <input type="date" name="date_paiement" value="{{ request('date_paiement') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                    <input type="text" name="mode_paiement" value="{{ request('mode_paiement') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="virement, cheque, especes...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <input type="text" name="statut" value="{{ request('statut') }}"
                           class="search-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                           placeholder="actif ou inactif">
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div id="paiements-table">
            @include('paiements._table', ['paiements' => $paiements])
        </div>
    </div>

    <div id="importModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Importer des paiements</h3>
                <button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('paiements.importer') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fichier CSV</label>
                    <input type="file" name="fichier" accept=".csv,.txt" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    <p class="mt-1 text-xs text-gray-500">Format : CSV séparé par des points-virgules (;)</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInputs = document.querySelectorAll('.search-input');
    var tableContainer = document.getElementById('paiements-table');
    var searchForm = document.getElementById('search-form');
    var searchUrl = '{{ route('paiements.index') }}';
    var debounceTimer = null;

    function performSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            var inputs = searchForm.querySelectorAll('input, select, textarea');
            var params = new URLSearchParams();
            for (var i = 0; i < inputs.length; i++) {
                var el = inputs[i];
                if (el.name && el.value.trim() !== '') {
                    params.append(el.name, el.value.trim());
                }
            }

            var qs = params.toString();
            var url = qs ? searchUrl + '?' + qs : searchUrl;

            tableContainer.classList.add('opacity-50');

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function (data) {
                if (data.html) { tableContainer.innerHTML = data.html; }
                window.history.replaceState({}, '', url);
            })
            .catch(function () { window.location.href = url; })
            .finally(function () { tableContainer.classList.remove('opacity-50'); });
        }, 300);
    }

    for (var i = 0; i < searchInputs.length; i++) {
        searchInputs[i].addEventListener('input', performSearch);
        searchInputs[i].addEventListener('change', performSearch);
    }

    tableContainer.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link || !link.closest('#pagination-links')) return;
        e.preventDefault();
        var url = link.getAttribute('href');
        tableContainer.classList.add('opacity-50');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
        .then(function (data) {
            if (data.html) { tableContainer.innerHTML = data.html; }
            window.history.replaceState({}, '', url);
        })
        .catch(function () { window.location.href = url; })
        .finally(function () { tableContainer.classList.remove('opacity-50'); });
    });
});
</script>
@endpush

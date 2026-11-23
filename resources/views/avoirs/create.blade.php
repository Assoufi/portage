@extends('layouts.app')

@section('title', 'Créer un Avoir')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Créer un avoir</h2>
        <a href="{{ route('avoirs.index') }}"
           class="inline-flex items-center justify-center rounded-lg bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 transition">
            Liste des avoirs
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-2">
            <div class="bg-white shadow-sm sm:rounded-lg h-full">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Factures à annuler</h3>
                    <p class="mt-1 text-sm text-gray-500">Sélectionnez une facture pour pré-remplir l'avoir.</p>

                    <form method="GET" action="{{ route('avoirs.create') }}" class="mt-4 space-y-3">
                        <input type="hidden" name="facture_id" value="{{ $donnees['facture_id'] ?? '' }}">
                        <div>
                            <input type="text" name="recherche" value="{{ request('recherche') }}"
                                   placeholder="N° facture, client, fournisseur…"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <select name="client_id" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                                <option value="">Tous les clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="fournisseur_id" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                                <option value="">Tous les fournisseurs</option>
                                @foreach($fournisseurs as $fournisseur)
                                    <option value="{{ $fournisseur->id }}" {{ request('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                                        {{ $fournisseur->nom }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-4 transition">
                            Filtrer
                        </button>
                    </form>
                </div>

                <div class="divide-y divide-gray-100 max-h-[34rem] overflow-y-auto">
                    @forelse($factures as $facture)
                        @php
                            $deja = (float) ($facture->avoirs_sum_montant_ttc ?? 0);
                            $restant = max(0, (float) $facture->montant_ttc - $deja);
                            $selectionnee = (string) ($donnees['facture_id'] ?? '') === (string) $facture->id;
                        @endphp
                        <button type="button"
                                data-select-facture="{{ $facture->id }}"
                                class="w-full text-left px-5 py-4 transition hover:bg-indigo-50 focus:outline-none focus:bg-indigo-50 {{ $selectionnee ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ $facture->numero_facture }}</p>
                                    <p class="text-sm text-gray-600 truncate">{{ $facture->client?->nom ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $facture->fournisseur?->nom ?? '—' }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $facture->montant_ttc_formate }}</p>
                                    <p class="text-xs text-gray-500">{{ $facture->date_facture_formatee }}</p>
                                    @if($restant <= 0.009 && $deja > 0)
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Annulée</span>
                                    @else
                                        <span class="inline-block mt-1 text-xs font-medium text-indigo-700">Reste {{ number_format($restant, 2, ',', ' ') }}</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-500">Aucune facture trouvée.</p>
                    @endforelse
                </div>

                @if($factures->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $factures->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="xl:col-span-3">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('avoirs.store') }}" id="avoirForm">
                        @csrf

                        @include('avoirs.partials.form')

                        <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                            <a href="{{ route('avoirs.index') }}"
                               class="inline-flex justify-center rounded-lg bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 transition">
                                Annuler
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 transition">
                                Enregistrer l'avoir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const urlTemplate = '{{ route('avoirs.donnees', ['facture' => '__FACTURE__']) }}';

                document.querySelectorAll('[data-select-facture]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const factureId = button.getAttribute('data-select-facture');
                        const url = urlTemplate.replace('__FACTURE__', factureId);

                        button.classList.add('opacity-60');

                        fetch(url, { headers: { 'Accept': 'application/json' } })
                            .then(function (response) {
                                if (!response.ok) throw new Error('Chargement impossible');
                                return response.json();
                            })
                            .then(function (data) {
                                window.avoirForm.applyDonnees(data);
                                document.querySelectorAll('[data-select-facture]').forEach(function (b) {
                                    b.classList.remove('bg-indigo-50', 'ring-1', 'ring-inset', 'ring-indigo-200');
                                });
                                button.classList.add('bg-indigo-50', 'ring-1', 'ring-inset', 'ring-indigo-200');
                                if (window.matchMedia('(max-width: 1279px)').matches) {
                                    document.getElementById('avoirForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                            })
                            .catch(function () {
                                alert('Impossible de charger les données de la facture.');
                            })
                            .finally(function () {
                                button.classList.remove('opacity-60');
                            });
                    });
                });
            });
        </script>
    @endpush
@endsection

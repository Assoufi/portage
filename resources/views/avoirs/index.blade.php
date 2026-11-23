@extends('layouts.app')

@section('title', 'Gestion des Avoirs')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Avoirs</h2>
        <a href="{{ route('avoirs.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 transition">
            + Nouvel avoir
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">Total avoirs</p>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['totalAvoirs'] }}</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">Montant annulé</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['montantTotal'], 2, ',', ' ') }}</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">Avoirs ce mois</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['avoirsMoisCourant'] }}</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <p class="text-sm text-gray-600">Factures concernées</p>
            <p class="text-2xl font-bold text-amber-600">{{ $stats['totalFactures'] }}</p>
        </div>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('avoirs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">N° avoir</label>
                    <input type="text" name="numero_avoir" value="{{ request('numero_avoir') }}"
                           placeholder="AV-2026-0001…"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="recherche" value="{{ request('recherche') }}"
                           placeholder="Facture, client, motif…"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                    <select name="client_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->nom }}
                            </option>
                        @endforeach
                    </select>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <div class="md:col-span-3 flex flex-wrap gap-3">
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 transition">
                        Filtrer
                    </button>
                    <a href="{{ route('avoirs.index') }}"
                       class="rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 transition">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° / Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facture</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fournisseur</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant TTC</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($avoirs as $avoir)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ $avoir->numero_avoir }}</p>
                                <p class="text-xs text-gray-500">{{ $avoir->date_avoir_formatee }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($avoir->facture)
                                    <a href="{{ route('factures.show', $avoir->facture) }}"
                                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ $avoir->facture->numero_facture }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $avoir->client?->nom ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $avoir->fournisseur?->nom ?? '—' }}</td>
                            <td class="px-4 py-3">{!! $avoir->statut_badge !!}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ $avoir->montant_ttc_formate }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('avoirs.show', $avoir) }}"
                                       class="text-xs font-semibold text-gray-600 hover:text-gray-900">Voir</a>
                                    <a href="{{ route('avoirs.edit', $avoir) }}"
                                       class="text-xs font-semibold text-blue-600 hover:text-blue-800">Modifier</a>
                                    <a href="{{ route('avoirs.pdf', $avoir) }}"
                                       class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">PDF</a>
                                    <form method="POST"
                                          action="{{ route('avoirs.destroy', $avoir) }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}"
                                          onsubmit="return confirm('Supprimer cet avoir ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                Aucun avoir enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($avoirs->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $avoirs->links() }}
            </div>
        @endif
    </div>
@endsection

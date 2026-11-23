@extends('layouts.app')

@section('title', 'Avoir '.$avoir->numero_avoir)

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Avoir {{ $avoir->numero_avoir }}</h2>
            <p class="text-sm text-gray-500">Émis le {{ $avoir->date_avoir_formatee }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('avoirs.index') }}"
               class="inline-flex items-center justify-center rounded-lg bg-gray-500 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 transition">
                Retour
            </a>
            <a href="{{ route('avoirs.edit', $avoir) }}"
               class="inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 transition">
                Modifier
            </a>
            <a href="{{ route('avoirs.pdf', ['avoir' => $avoir, 'signe' => 1]) }}"
               class="inline-flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-4 transition">
                PDF signé
            </a>
            <a href="{{ route('avoirs.pdf', ['avoir' => $avoir, 'signe' => 0]) }}"
               class="inline-flex items-center justify-center rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-sm font-semibold py-2 px-4 transition">
                PDF non signé
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    {!! $avoir->statut_badge !!}
                    <span class="text-sm text-gray-600">{{ $avoir->type_label }}</span>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Lignes annulées</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Désignation</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prix unitaire</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total HT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($avoir->details as $detail)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $detail->designation }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $detail->quantite }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700">{{ $detail->prix_unitaire_formate }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">{{ $detail->montant_ht_formate }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Aucune ligne.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Total HT</td>
                                <td class="px-4 py-2 text-right text-sm font-bold text-gray-900">{{ $avoir->total_ht_formate }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-gray-700">TVA ({{ number_format($avoir->taux_tva, 2, ',', ' ') }}%)</td>
                                <td class="px-4 py-2 text-right text-sm font-bold text-gray-900">
                                    {{ number_format(max(0, ($avoir->montant_ttc ?? 0) - ($avoir->total_ht ?? 0)), 2, ',', ' ') }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-gray-700 border-t">Montant TTC</td>
                                <td class="px-4 py-2 text-right text-sm font-bold text-gray-900 border-t">{{ $avoir->montant_ttc_formate }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($avoir->motif)
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 mb-1">Motif</h3>
                        <p class="text-sm text-gray-800">{{ $avoir->motif }}</p>
                    </div>
                @endif

                @if($avoir->remarques)
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 mb-1">Remarques</h3>
                        <p class="text-sm text-gray-800 whitespace-pre-line">{{ $avoir->remarques }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 mb-3">Facture d'origine</h3>
                @if($avoir->facture)
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">N° facture</dt>
                            <dd>
                                <a href="{{ route('factures.show', $avoir->facture) }}"
                                   class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ $avoir->facture->numero_facture }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Date</dt>
                            <dd class="font-medium text-gray-900">{{ $avoir->facture->date_facture_formatee }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Montant TTC</dt>
                            <dd class="font-medium text-gray-900">{{ $avoir->facture->montant_ttc_formate }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-gray-500">Facture indisponible.</p>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 mb-3">Acteurs</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Client</dt>
                        <dd class="font-medium text-gray-900">{{ $avoir->client?->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Fournisseur</dt>
                        <dd class="font-medium text-gray-900">{{ $avoir->fournisseur?->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Consultant</dt>
                        <dd class="font-medium text-gray-900">{{ $avoir->consultant?->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Mission</dt>
                        <dd class="font-medium text-gray-900">{{ $avoir->mission?->titre ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('avoirs.destroy', $avoir) }}"
                      onsubmit="return confirm('Supprimer cet avoir ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 transition">
                        Supprimer l'avoir
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

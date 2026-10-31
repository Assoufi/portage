@extends('layouts.app')

@section('title', 'Commande '.$commande->num_commande)

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Commande {{ $commande->num_commande }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('commandes.edit', $commande) }}"
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('commandes.index') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Retour
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Informations</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">N° Commande</dt>
                        <dd class="font-mono font-medium text-gray-900">{{ $commande->num_commande }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Date commande</dt>
                        <dd class="font-medium text-gray-900">{{ $commande->date_commande?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Période</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $commande->date_debut?->format('d/m/Y') ?? '—' }}
                            au
                            {{ $commande->date_fin?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Fournisseur</dt>
                        <dd class="font-medium text-gray-900">{{ $commande->mission?->fournisseur?->nom ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Mission</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $commande->mission?->titre ?? 'Mission #'.$commande->mission_id }}
                        </dd>
                    </div>
                    @if($commande->mission?->consultant)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Consultant</dt>
                            <dd class="font-medium text-gray-900">{{ $commande->mission->consultant->nom }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Statut</dt>
                        <dd>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $commande->statut === 'ACTIF' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Commande::STATUTS[$commande->statut] ?? $commande->statut }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Montants</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Quantité (jours)</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($commande->quantite, 2, ',', ' ') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">TJM</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($commande->tjm, 2, ',', ' ') }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <dt class="font-semibold text-gray-700">Montant HT</dt>
                        <dd class="font-bold text-gray-900">{{ number_format($commande->quantite * $commande->tjm, 2, ',', ' ') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection

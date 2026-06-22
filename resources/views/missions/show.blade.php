{{-- resources/views/missions/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Détails de la Mission')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mission #{{ $mission->id }}
        </h2>
        <div class="space-x-2">
            <a href="{{ route('missions.edit', $mission) }}" 
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('missions.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Retour
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informations principales -->
        <div class="lg:col-span-2">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la mission</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Consultant</p>
                            <p class="font-medium">{{ $mission->consultant->nom }}</p>
                            <p class="text-sm text-gray-500">{{ $mission->consultant->email }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Client</p>
                            <p class="font-medium">{{ $mission->client->email }}</p>
                            <p class="text-sm text-gray-500">ICE: {{ $mission->client->ice }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Fournisseur</p>
                            <p class="font-medium">{{ $mission->fournisseur->email }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Statut</p>
                            {!! $mission->statut_badge !!}
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Période</p>
                            <p class="font-medium">
                                {{ \Carbon\Carbon::parse($mission->date_debut)->format('d/m/Y') }}
                                @if($mission->date_fin)
                                    → {{ \Carbon\Carbon::parse($mission->date_fin)->format('d/m/Y') }}
                                @else
                                    → En cours
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">Durée: {{ $mission->duree_formatted }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Délai de paiement</p>
                            <p class="font-medium">{{ $mission->delai_paiement }} jours</p>
                            @if($mission->date_paiement)
                                <p class="text-sm text-gray-500">
                                    Date d'échéance: {{ $mission->date_paiement->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                        
                        @if($mission->remarques)
                            <div>
                                <p class="text-sm text-gray-600">Remarques</p>
                                <p class="text-sm">{{ $mission->remarques }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations financières -->
        <div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails financiers</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">TJM:</span>
                            <span class="font-medium">{{ number_format($mission->tjm, 2) }} {{ $mission->client->devise }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Taux (jours):</span>
                            <span class="font-medium">{{ $mission->taux }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600">Coût total:</span>
                            <span class="font-medium">{{ number_format($coutTotal, 2) }} {{ $mission->client->devise }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Prix de vente:</span>
                            <span class="font-medium text-green-600">{{ number_format($mission->prix_vente, 2) }} {{ $mission->client->devise }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600 font-semibold">Marge:</span>
                            <span class="font-bold {{ $marge >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($marge, 2) }} {{ $mission->client->devise }}
                                ({{ number_format($margePourcentage, 1) }}%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
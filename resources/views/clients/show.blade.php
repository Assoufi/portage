{{-- resources/views/clients/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Détails du Client')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Client : {{ $client->nom }}
        </h2>
        <div class="space-x-2">
            <a href="{{ route('clients.edit', $client) }}" 
               class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                Modifier
            </a>
            <a href="{{ route('clients.index') }}" 
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du client</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nom du client</p>
                            <p class="font-medium">{{ $client->nom }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-medium">{{ $client->email }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">ICE</p>
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $client->ice }}</code>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Adresse</p>
                            <p class="font-medium">{{ $client->adresse }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">TVA</p>
                            <p class="font-medium">{{ $client->tva_formatted }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Devise</p>
                            <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded">{{ $client->devise }}</span>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Statut</p>
                            {!! $client->statut_badge !!}
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Date d'inscription</p>
                            <p class="font-medium">{{ $client->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Dernière modification</p>
                            <p class="font-medium">{{ $client->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total missions</span>
                            <span class="font-bold text-xl">{{ $stats['missions_total'] }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Missions en cours</span>
                            <span class="font-bold text-blue-600">{{ $stats['missions_encours'] }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Missions terminées</span>
                            <span class="font-bold text-green-600">{{ $stats['missions_terminees'] }}</span>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-gray-600">CA total</span>
                            <span class="font-bold text-purple-600">{{ number_format($stats['ca_total'], 2) }} {{ $client->devise }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dernières missions -->
    @if($client->missions->count() > 0)
    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dernières missions</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mission</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Consultant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->missions as $mission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <a href="{{ route('missions.show', $mission) }}" class="text-blue-600 hover:text-blue-900">
                                        Mission #{{ $mission->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">{{ $mission->consultant->nom }}</td>
                                <td class="px-6 py-4">
                                    {{ $mission->date_debut->format('d/m/Y') }}
                                    @if($mission->date_fin)
                                        → {{ $mission->date_fin->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">{{ number_format($mission->prix_vente, 2) }} {{ $client->devise }}</td>
                                <td class="px-6 py-4">{!! $mission->statut_badge !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection
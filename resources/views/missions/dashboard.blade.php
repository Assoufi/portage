{{-- resources/views/missions/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Dashboard
    </h2>
@endsection

@section('content')
    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-lg p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total missions</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_missions'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-lg p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Missions en cours</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['missions_encours'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-lg p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Missions terminées</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['missions_terminees'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-lg p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">CA total</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['ca_total'], 2) }} MAD</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Clients et Consultants -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Clients</h3>
                <div class="space-y-3">
                    @foreach($topClients as $client)
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $client->email }}</p>
                                <p class="text-sm text-gray-500">{{ $client->missions_count }} missions</p>
                            </div>
                            <p class="font-bold text-green-600">{{ number_format($client->missions_sum_prix_vente ?? 0, 2) }} MAD</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Consultants</h3>
                <div class="space-y-3">
                    @foreach($topConsultants as $consultant)
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $consultant->nom }}</p>
                                <p class="text-sm text-gray-500">{{ $consultant->missions_count }} missions</p>
                            </div>
                            <p class="font-bold text-blue-600">{{ number_format($consultant->missions_sum_prix_vente ?? 0, 2) }} MAD</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <!-- Évolution mensuelle -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Évolution des missions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Période</th>
                            <th class="text-right py-2">Nombre de missions</th>
                            <th class="text-right py-2">CA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($evolution as $data)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2">{{ $data->annee }}-{{ str_pad($data->mois, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="text-right py-2">{{ $data->total }}</td>
                                <td class="text-right py-2 font-medium">{{ number_format($data->ca, 2) }} MAD</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
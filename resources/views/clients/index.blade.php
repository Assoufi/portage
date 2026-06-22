{{-- resources/views/clients/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestion des Clients')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Clients
        </h2>
        <a href="{{ route('clients.create') }}" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            + Nouveau Client
        </a>
    </div>
@endsection

@section('content')
    <!-- Filtres et recherche -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('clients.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Nom, email, ICE ou adresse..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="statut" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        <option value="actif" {{ request('statut') == 'actif' ? 'selected' : '' }}>Actifs</option>
                        <option value="inactif" {{ request('statut') == 'inactif' ? 'selected' : '' }}>Inactifs</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Devise</label>
                    <select name="devise" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Toutes</option>
                        @foreach($devises as $devise)
                            <option value="{{ $devise }}" {{ request('devise') == $devise ? 'selected' : '' }}>{{ $devise }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                        Filtrer
                    </button>
                    @if(request()->anyFilled(['search', 'statut', 'devise']))
                        <a href="{{ route('clients.index') }}" class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-center">
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau des clients -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            <a href="{{ route('clients.index', array_merge(request()->query(), ['sort' => 'nom', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                               class="hover:text-gray-700 flex items-center space-x-1">
                                <span>Nom</span>
                                @if(request('sort') == 'nom')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="{{ request('direction') == 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"></path>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ICE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">TVA</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Devise</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($clients as $client)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $client->nom }}</div>
                                <div class="text-xs text-gray-500">ID: #{{ $client->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $client->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $client->ice }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate">{{ $client->adresse }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm">{{ $client->tva_formatted }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded">{{ $client->devise }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {!! $client->statut_badge !!}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <a href="{{ route('clients.show', $client) }}" class="text-blue-600 hover:text-blue-900">Voir</a>
                                <a href="{{ route('clients.edit', $client) }}" class="text-yellow-600 hover:text-yellow-900">Modifier</a>
                                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?')">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                Aucun client trouvé.
                                <a href="{{ route('clients.create') }}" class="text-blue-600 hover:text-blue-900 ml-2">Créer un client</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $clients->withQueryString()->links() }}
        </div>
    </div>
@endsection
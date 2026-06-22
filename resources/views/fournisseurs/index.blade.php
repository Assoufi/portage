{{-- resources/views/fournisseurs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestion des Fournisseurs')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Fournisseurs
        </h2>
        <a href="{{ route('fournisseurs.create') }}" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            + Nouveau Fournisseur
        </a>
    </div>
@endsection

@section('content')
    <!-- Filtres et recherche -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('fournisseurs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Email, ICE ou adresse..."
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
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                        Filtrer
                    </button>
                    @if(request()->anyFilled(['search', 'statut']))
                        <a href="{{ route('fournisseurs.index') }}" class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-center">
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau des fournisseurs -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ICE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Taux</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($fournisseurs as $fournisseur)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $fournisseur->nom }}</div>
                                <div class="text-xs text-gray-500">ID: #{{ $fournisseur->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $fournisseur->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $fournisseur->ice }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate">{{ $fournisseur->adresse }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-medium">{{ $fournisseur->taux_formatted }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {!! $fournisseur->statut_badge !!}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <a href="{{ route('fournisseurs.show', $fournisseur) }}" class="text-blue-600 hover:text-blue-900">Voir</a>
                                <a href="{{ route('fournisseurs.edit', $fournisseur) }}" class="text-yellow-600 hover:text-yellow-900">Modifier</a>
                                <form action="{{ route('fournisseurs.destroy', $fournisseur) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?')">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Aucun fournisseur trouvé.
                                <a href="{{ route('fournisseurs.create') }}" class="text-blue-600 hover:text-blue-900 ml-2">Créer un fournisseur</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $fournisseurs->withQueryString()->links() }}
        </div>
    </div>
@endsection
{{-- resources/views/consultants/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestion des Consultants')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Consultants
        </h2>
        <a href="{{ route('consultants.create') }}" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            + Nouveau Consultant
        </a>
    </div>
@endsection

@section('content')
    <!-- Filtres et recherche -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('consultants.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Nom, email ou téléphone..."
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
                    <button type="submit" class="flex-1 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Filtrer
                    </button>
                    @if(request()->anyFilled(['search', 'statut']))
                        <a href="{{ route('consultants.index') }}" class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 text-center">
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau des consultants -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('consultants.index', array_merge(request()->query(), ['sort' => 'nom', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mode de paiement
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($consultants as $consultant)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $consultant->nom }}</div>
                                <div class="text-sm text-gray-500">ID: #{{ $consultant->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $consultant->email }}</div>
                                <div class="text-sm text-gray-500">{{ $consultant->tel }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $consultant->mode_paiement_label }}</div>
                                @if($consultant->rib)
                                    <div class="text-xs text-gray-500">RIB: {{ substr($consultant->rib, -4) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                {!! $consultant->statut_badge !!}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <a href="{{ route('consultants.show', $consultant) }}" 
                                   class="text-blue-600 hover:text-blue-900">Voir</a>
                                <a href="{{ route('consultants.edit', $consultant) }}" 
                                   class="text-yellow-600 hover:text-yellow-900">Modifier</a>
                                <form action="{{ route('consultants.destroy', $consultant) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce consultant ? Cette action est irréversible.')">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Aucun consultant trouvé.
                                <a href="{{ route('consultants.create') }}" class="text-blue-600 hover:text-blue-900 ml-2">Créer un consultant</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $consultants->withQueryString()->links() }}
        </div>
    </div>
@endsection
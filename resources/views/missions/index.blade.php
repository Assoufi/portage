{{-- resources/views/missions/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestion des Missions')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Missions
        </h2>
        <a href="{{ route('missions.create') }}" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            + Nouvelle Mission
        </a>
    </div>
@endsection

@section('content')
    <!-- Filtres -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('missions.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Recherche générale -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche globale</label>
                    <input type="text" name="search" id="globalSearch" 
                           value="{{ request('search') }}" 
                           placeholder="Consultant, Client, Fournisseur, Titre, Formule..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                </div>

                <!-- Consultant avec autocomplétion AJAX -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Consultant</label>
                    <div class="relative">
                        <input type="text" name="consultant_nom" id="consultantSearch" 
                               value="{{ request('consultant_nom') }}" 
                               placeholder="Saisir le nom..."
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <input type="hidden" name="consultant_id" id="consultantId" value="{{ request('consultant_id') }}">
                        <div id="consultantSuggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto"></div>
                    </div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="statut" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                        <option value="">Tous</option>
                        <option value="encours" {{ request('statut') == 'encours' ? 'selected' : '' }}>En cours</option>
                        <option value="terminees" {{ request('statut') == 'terminees' ? 'selected' : '' }}>Terminées</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau des missions -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Consultant
                        </th>                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dates
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Formule
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Montants
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
                    @forelse($missions as $mission)
                        <tr class="hover:bg-gray-50 transition duration-150">                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $mission->consultant->nom }}</div>
                                <div class="text-sm text-gray-500">{{ $mission->titre ?: 'Mission #' . $mission->id }}</div>
                            </td>                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($mission->date_debut)->format('d/m/Y') }}
                                    @if($mission->date_fin)
                                        → {{ \Carbon\Carbon::parse($mission->date_fin)->format('d/m/Y') }}
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">
                                    Durée: {{ $mission->duree_formatted }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $mission->formule_formatted }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ number_format($mission->prix_vente, 2) }} {{ $mission->client->devise }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    TJM: {{ number_format($mission->tjm, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {!! $mission->statut_badge !!}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    {{-- Voir --}}
                                    <a href="{{ route('missions.show', $mission) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-800 transition duration-150"
                                       title="Voir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    {{-- Modifier --}}
                                    <a href="{{ route('missions.edit', $mission) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 hover:text-yellow-800 transition duration-150"
                                       title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    {{-- Supprimer --}}
                                    <form action="{{ route('missions.destroy', $mission) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-800 transition duration-150"
                                                title="Supprimer"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette mission ?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                Aucune mission trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4">
            {{ $missions->links() }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const consultantInput = document.getElementById('consultantSearch');
        const consultantIdInput = document.getElementById('consultantId');
        const suggestionsContainer = document.getElementById('consultantSuggestions');
        
        let debounceTimer;
        let selectedIndex = -1;
        
        consultantInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(debounceTimer);
            
            if (query.length < 2) {
                hideSuggestions();
                consultantIdInput.value = '';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetchConsultants(query);
            }, 300);
        });
        
        consultantInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                fetchConsultants(this.value.trim());
            }
        });
        
        consultantInput.addEventListener('keydown', function(e) {
            const items = suggestionsContainer.querySelectorAll('.suggestion-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                selectConsultant(items[selectedIndex]);
            } else if (e.key === 'Escape') {
                hideSuggestions();
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!consultantInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                hideSuggestions();
            }
        });
        
        function fetchConsultants(query) {
            fetch(`/api/consultants/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displaySuggestions(data);
                })
                .catch(error => {
                    console.error('Erreur recherche consultants:', error);
                });
        }
        
        function displaySuggestions(consultants) {
            if (consultants.length === 0) {
                suggestionsContainer.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">Aucun consultant trouvé</div>';
                suggestionsContainer.classList.remove('hidden');
                return;
            }
            
            suggestionsContainer.innerHTML = consultants.map((c, index) => `
                <div class="suggestion-item px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                     data-id="${c.id}" 
                     data-nom="${c.nom}"
                     data-email="${c.email}">
                    <div class="font-medium text-gray-900">${c.nom}</div>
                    <div class="text-xs text-gray-500">${c.email}</div>
                </div>
            `).join('');
            
            suggestionsContainer.classList.remove('hidden');
            selectedIndex = -1;
            
            suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', () => selectConsultant(item));
            });
        }
        
        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('bg-blue-50');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-blue-50');
                }
            });
        }
        
        function selectConsultant(item) {
            consultantInput.value = item.dataset.nom;
            consultantIdInput.value = item.dataset.id;
            hideSuggestions();
        }
        
        function hideSuggestions() {
            suggestionsContainer.classList.add('hidden');
            selectedIndex = -1;
        }
    });
</script>
@endpush
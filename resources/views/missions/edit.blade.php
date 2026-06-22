{{-- resources/views/missions/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Modifier la Mission')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier la mission
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('missions.update', $mission) }}" id="missionForm">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Consultant -->
                    <div>
                        <label for="consultant_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Consultant *
                        </label>
                        <select name="consultant_id" id="consultant_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('consultant_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un consultant</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}" {{ old('consultant_id', $mission->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->nom }} - {{ $consultant->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultant_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Client -->
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Client *
                        </label>
                        <select name="client_id" id="client_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('client_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $mission->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->email }} - {{ $client->devise }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Fournisseur -->
                    <div>
                        <label for="fournisseur_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Fournisseur *
                        </label>
                        <select name="fournisseur_id" id="fournisseur_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('fournisseur_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id', $mission->fournisseur_id) == $fournisseur->id ? 'selected' : '' }}>
                                    {{ $fournisseur->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('fournisseur_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Taux -->
                    <div>
                        <label for="taux" class="block text-sm font-medium text-gray-700 mb-2">
                            Taux (jours) *
                        </label>
                        <input type="number" name="taux" id="taux" step="0.01" 
                               value="{{ old('taux', $mission->taux) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('taux') border-red-500 @enderror"
                               required>
                        @error('taux')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- TJM -->
                    <div>
                        <label for="tjm" class="block text-sm font-medium text-gray-700 mb-2">
                            TJM (Taux Journalier Moyen) *
                        </label>
                        <input type="number" name="tjm" id="tjm" step="0.01" 
                               value="{{ old('tjm', $mission->tjm) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('tjm') border-red-500 @enderror"
                               required>
                        @error('tjm')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Prix de vente -->
                    <div>
                        <label for="prix_vente" class="block text-sm font-medium text-gray-700 mb-2">
                            Prix de vente *
                        </label>
                        <input type="number" name="prix_vente" id="prix_vente" step="0.01" 
                               value="{{ old('prix_vente', $mission->prix_vente) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('prix_vente') border-red-500 @enderror"
                               required>
                        <p id="margeInfo" class="text-xs mt-1"></p>
                        @error('prix_vente')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Date début -->
                    <div>
                        <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de début *
                        </label>
                        <input type="date" name="date_debut" id="date_debut" 
                               value="{{ old('date_debut', $mission->date_debut->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_debut') border-red-500 @enderror"
                               required>
                        @error('date_debut')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Date fin -->
                    <div>
                        <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de fin
                        </label>
                        <input type="date" name="date_fin" id="date_fin" 
                               value="{{ old('date_fin', $mission->date_fin?->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_fin') border-red-500 @enderror">
                        <p id="dateError" class="text-red-500 text-xs mt-1 hidden"></p>
                        @error('date_fin')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Délai paiement -->
                    <div>
                        <label for="delai_paiement" class="block text-sm font-medium text-gray-700 mb-2">
                            Délai de paiement (jours) *
                        </label>
                        <input type="number" name="delai_paiement" id="delai_paiement" 
                               value="{{ old('delai_paiement', $mission->delai_paiement) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('delai_paiement') border-red-500 @enderror"
                               required>
                        @error('delai_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Remarques -->
                    <div class="md:col-span-2">
                        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">
                            Remarques
                        </label>
                        <textarea name="remarques" id="remarques" rows="4" 
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques', $mission->remarques) }}</textarea>
                        @error('remarques')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('missions.index') }}" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Script identique à celui du create
    document.addEventListener('DOMContentLoaded', function() {
        const tauxInput = document.getElementById('taux');
        const tjmInput = document.getElementById('tjm');
        const prixVenteInput = document.getElementById('prix_vente');
        const margeInfo = document.getElementById('margeInfo');
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');
        const dateError = document.getElementById('dateError');
        
        function calculateMarge() {
            const taux = parseFloat(tauxInput.value) || 0;
            const tjm = parseFloat(tjmInput.value) || 0;
            const prixVente = parseFloat(prixVenteInput.value) || 0;
            
            const coutTotal = taux * tjm;
            const marge = prixVente - coutTotal;
            const margePourcentage = coutTotal > 0 ? (marge / coutTotal) * 100 : 0;
            
            if (prixVente > 0 && coutTotal > 0) {
                if (marge > 0) {
                    margeInfo.innerHTML = `<span class="text-green-600">✓ Marge bénéficiaire: ${marge.toFixed(2)} (${margePourcentage.toFixed(1)}%)</span>`;
                } else {
                    margeInfo.innerHTML = `<span class="text-red-600">⚠ Marge négative: ${marge.toFixed(2)} (${margePourcentage.toFixed(1)}%)</span>`;
                }
            } else {
                margeInfo.innerHTML = '';
            }
        }
        
        function validateDates() {
            if (dateDebut.value && dateFin.value) {
                if (new Date(dateFin.value) < new Date(dateDebut.value)) {
                    dateError.classList.remove('hidden');
                    dateError.textContent = 'La date de fin doit être postérieure ou égale à la date de début.';
                    return false;
                } else {
                    dateError.classList.add('hidden');
                    return true;
                }
            }
            return true;
        }
        
        tauxInput.addEventListener('input', calculateMarge);
        tjmInput.addEventListener('input', calculateMarge);
        prixVenteInput.addEventListener('input', calculateMarge);
        dateFin.addEventListener('change', validateDates);
        dateDebut.addEventListener('change', validateDates);
        
        document.getElementById('missionForm').addEventListener('submit', function(e) {
            if (!validateDates()) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs de dates avant de soumettre le formulaire.');
            }
        });
        
        calculateMarge();
    });
</script>
@endpush
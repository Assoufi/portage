@extends('layouts.app')

@section('title', 'Modifier la Répartition')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier la répartition
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('repartitions.update', $repartition) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="paiement_id" class="block text-sm font-medium text-gray-700 mb-2">Paiement *</label>
                        <select name="paiement_id" id="paiement_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('paiement_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un paiement</option>
                            @foreach($paiements as $p)
                                <option value="{{ $p->id }}" {{ old('paiement_id', $repartition->paiement_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->reference }} - {{ number_format($p->montant, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('paiement_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="consultant_id" class="block text-sm font-medium text-gray-700 mb-2">Consultant *</label>
                        <select name="consultant_id" id="consultant_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('consultant_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un consultant</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}" {{ old('consultant_id', $repartition->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultant_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="montant" class="block text-sm font-medium text-gray-700 mb-2">Montant *</label>
                        <input type="number" name="montant" id="montant" step="0.01"
                               value="{{ old('montant', $repartition->montant) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('montant') border-red-500 @enderror"
                               required>
                        @error('montant')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_paiement" class="block text-sm font-medium text-gray-700 mb-2">Date de paiement *</label>
                        <input type="date" name="date_paiement" id="date_paiement"
                               value="{{ old('date_paiement', $repartition->date_paiement->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_paiement') border-red-500 @enderror"
                               required>
                        @error('date_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mode_paiement" class="block text-sm font-medium text-gray-700 mb-2">Mode de paiement *</label>
                        <select name="mode_paiement" id="mode_paiement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mode_paiement') border-red-500 @enderror"
                                required>
                            <option value="virement" {{ old('mode_paiement', $repartition->mode_paiement?->value) == 'virement' ? 'selected' : '' }}>Virement bancaire</option>
                            <option value="cheque" {{ old('mode_paiement', $repartition->mode_paiement?->value) == 'cheque' ? 'selected' : '' }}>Chèque</option>
                            <option value="especes" {{ old('mode_paiement', $repartition->mode_paiement?->value) == 'especes' ? 'selected' : '' }}>Espèces</option>
                        </select>
                        @error('mode_paiement')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="rib" class="block text-sm font-medium text-gray-700 mb-2">RIB</label>
                        <input type="text" name="rib" id="rib"
                               value="{{ old('rib', $repartition->rib) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('rib') border-red-500 @enderror">
                        @error('rib')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="banque" class="block text-sm font-medium text-gray-700 mb-2">Banque</label>
                        <input type="text" name="banque" id="banque"
                               value="{{ old('banque', $repartition->banque) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('banque') border-red-500 @enderror">
                        @error('banque')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="text" name="telephone" id="telephone"
                               value="{{ old('telephone', $repartition->telephone) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('telephone') border-red-500 @enderror">
                        @error('telephone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">Remarques</label>
                        <textarea name="remarques" id="remarques" rows="4"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques', $repartition->remarques) }}</textarea>
                        @error('remarques')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('repartitions.index') }}"
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

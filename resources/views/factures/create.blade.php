@extends('layouts.app')

@section('title', 'Créer une Facture')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Créer une nouvelle facture
    </h2>
@endsection

@section('content')
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('factures.store') }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fournisseur_id" class="block text-sm font-medium text-gray-700 mb-2">Fournisseur *</label>
                        <select name="fournisseur_id" id="fournisseur_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('fournisseur_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id') == $fournisseur->id ? 'selected' : '' }}>
                                    {{ $fournisseur->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('fournisseur_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                        <select name="client_id" id="client_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('client_id') border-red-500 @enderror"
                                required>
                            <option value="">Sélectionner un client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="numero_facture" class="block text-sm font-medium text-gray-700 mb-2">Numéro de facture *</label>
                        <input type="text" name="numero_facture" id="numero_facture"
                               value="{{ old('numero_facture', $numero) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_facture') border-red-500 @enderror"
                               required>
                        @error('numero_facture')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="numero_bcm" class="block text-sm font-medium text-gray-700 mb-2">Numéro BCM</label>
                        <input type="text" name="numero_bcm" id="numero_bcm"
                               value="{{ old('numero_bcm') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_bcm') border-red-500 @enderror">
                        @error('numero_bcm')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_facture" class="block text-sm font-medium text-gray-700 mb-2">Date de facture *</label>
                        <input type="date" name="date_facture" id="date_facture"
                               value="{{ old('date_facture', date('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_facture') border-red-500 @enderror"
                               required>
                        @error('date_facture')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_echeance" class="block text-sm font-medium text-gray-700 mb-2">Date d'échéance</label>
                        <input type="date" name="date_echeance" id="date_echeance"
                               value="{{ old('date_echeance') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_echeance') border-red-500 @enderror">
                        @error('date_echeance')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="designation" class="block text-sm font-medium text-gray-700 mb-2">Désignation</label>
                        <input type="text" name="designation" id="designation"
                               value="{{ old('designation') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('designation') border-red-500 @enderror">
                        @error('designation')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="beneficiaire" class="block text-sm font-medium text-gray-700 mb-2">Bénéficiaire</label>
                        <input type="text" name="beneficiaire" id="beneficiaire"
                               value="{{ old('beneficiaire') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('beneficiaire') border-red-500 @enderror">
                        @error('beneficiaire')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantite" class="block text-sm font-medium text-gray-700 mb-2">Quantité</label>
                        <input type="number" name="quantite" id="quantite" min="1"
                               value="{{ old('quantite') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('quantite') border-red-500 @enderror">
                        @error('quantite')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="prix_unitaire" class="block text-sm font-medium text-gray-700 mb-2">Prix unitaire HT</label>
                        <input type="number" name="prix_unitaire" id="prix_unitaire" step="0.01" min="0"
                               value="{{ old('prix_unitaire') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('prix_unitaire') border-red-500 @enderror">
                        @error('prix_unitaire')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="total_ht" class="block text-sm font-medium text-gray-700 mb-2">Total HT</label>
                        <input type="number" name="total_ht" id="total_ht" step="0.01" min="0"
                               value="{{ old('total_ht') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('total_ht') border-red-500 @enderror">
                        @error('total_ht')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tva" class="block text-sm font-medium text-gray-700 mb-2">TVA (%)</label>
                        <input type="number" name="tva" id="tva" step="0.01" min="0" max="100"
                               value="{{ old('tva', 20.00) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('tva') border-red-500 @enderror">
                        @error('tva')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="montant" class="block text-sm font-medium text-gray-700 mb-2">Montant TTC *</label>
                        <input type="number" name="montant" id="montant" step="0.01" min="0.01"
                               value="{{ old('montant') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('montant') border-red-500 @enderror"
                               required>
                        @error('montant')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="date_reception" class="block text-sm font-medium text-gray-700 mb-2">Date de réception</label>
                        <input type="date" name="date_reception" id="date_reception"
                               value="{{ old('date_reception') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_reception') border-red-500 @enderror">
                        @error('date_reception')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">Remarques</label>
                        <textarea name="remarques" id="remarques" rows="4"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('remarques') border-red-500 @enderror">{{ old('remarques') }}</textarea>
                        @error('remarques')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('factures.index') }}"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Créer la facture
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

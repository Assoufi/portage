{{-- resources/views/fournisseurs/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Modifier le Fournisseur')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier le fournisseur : {{ $fournisseur->nom }}
    </h2>
@endsection

@section('content')
    <div x-data="fournisseurForm()" x-init="init()" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('fournisseurs.update', $fournisseur) }}" @submit.prevent="validateAndSubmit">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nom -->
                    <div class="md:col-span-2">
                        <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom du fournisseur *
                        </label>
                        <input type="text" name="nom" id="nom" x-model="form.nom"
                               @input="validateNom()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.nom ? 'border-red-500' : 'border-gray-300'"
                               placeholder="Nom de l'entreprise">
                        <p x-show="errors.nom" x-text="errors.nom" class="text-red-500 text-xs mt-1"></p>
                    </div>

                    <!-- Adresse -->
                    <div class="md:col-span-2">
                        <label for="adresse" class="block text-sm font-medium text-gray-700 mb-2">
                            Adresse
                        </label>
                        <textarea name="adresse" id="adresse" rows="3" x-model="form.adresse"
                                  @input="validateAdresse()"
                                  class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                  :class="errors.adresse ? 'border-red-500' : 'border-gray-300'"></textarea>
                        <p x-show="errors.adresse" x-text="errors.adresse" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email" name="email" id="email" x-model="form.email"
                               @input="validateEmail()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.email ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.email" x-text="errors.email" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="emailValid && !errors.email && form.email" class="text-green-500 text-xs mt-1">✓ Email valide</p>
                    </div>
                    
                    <!-- ICE -->
                    <div>
                        <label for="ice" class="block text-sm font-medium text-gray-700 mb-2">
                            ICE (15 caractères)
                        </label>
                        <input type="text" name="ice" id="ice" x-model="form.ice"
                               @input="validateIce()"
                               maxlength="15"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 uppercase"
                               :class="errors.ice ? 'border-red-500' : 'border-gray-300'"
                               placeholder="123456789012345">
                        <p x-show="errors.ice" x-text="errors.ice" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="iceValid && !errors.ice && form.ice" class="text-green-500 text-xs mt-1">✓ Format ICE valide</p>
                    </div>
                    
                    <!-- Taux -->
                    <div>
                        <label for="taux" class="block text-sm font-medium text-gray-700 mb-2">
                            Taux (%)
                        </label>
                        <input type="number" name="taux" id="taux" x-model="form.taux" step="0.01"
                               @input="validateTaux()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.taux ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.taux" x-text="errors.taux" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Ville -->
                    <div>
                        <label for="ville" class="block text-sm font-medium text-gray-700 mb-2">
                            Ville
                        </label>
                        <input type="text" name="ville" id="ville" x-model="form.ville"
                               @input="validateVille()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.ville ? 'border-red-500' : 'border-gray-300'"
                               placeholder="Ville">
                        <p x-show="errors.ville" x-text="errors.ville" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Nom du responsable -->
                    <div>
                        <label for="nom_responsable" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom du responsable
                        </label>
                        <input type="text" name="nom_responsable" id="nom_responsable" x-model="form.nom_responsable"
                               @input="validateNomResponsable()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.nom_responsable ? 'border-red-500' : 'border-gray-300'"
                               placeholder="Nom du responsable">
                        <p x-show="errors.nom_responsable" x-text="errors.nom_responsable" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- RIB -->
                    <div>
                        <label for="rib" class="block text-sm font-medium text-gray-700 mb-2">
                            RIB
                        </label>
                        <input type="text" name="rib" id="rib" x-model="form.rib"
                               @input="validateRib()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.rib ? 'border-red-500' : 'border-gray-300'"
                               placeholder="RIB">
                        <p x-show="errors.rib" x-text="errors.rib" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Statut
                        </label>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="statut" value="1" x-model="form.statut" class="form-radio text-blue-600">
                                <span class="ml-2">Actif</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="statut" value="0" x-model="form.statut" class="form-radio text-red-600">
                                <span class="ml-2">Inactif</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('fournisseurs.index') }}" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting || !isFormValid"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300"
                            :class="{'opacity-50 cursor-not-allowed': !isFormValid}">
                        <span x-show="!isSubmitting">Mettre à jour</span>
                        <span x-show="isSubmitting">Mise à jour en cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function fournisseurForm() {
            return {
                form: {
                    nom: '{{ old('nom', $fournisseur->nom) }}',
                    adresse: '{{ old('adresse', $fournisseur->adresse) }}',
                    email: '{{ old('email', $fournisseur->email) }}',
                    ice: '{{ old('ice', $fournisseur->ice) }}',
                    taux: '{{ old('taux', $fournisseur->taux) }}',
                    ville: '{{ old('ville', $fournisseur->ville) }}',
                    nom_responsable: '{{ old('nom_responsable', $fournisseur->nom_responsable) }}',
                    rib: '{{ old('rib', $fournisseur->rib) }}',
                    statut: '{{ old('statut', $fournisseur->statut ? '1' : '0') }}'
                },
                errors: {},
                isSubmitting: false,
                emailValid: false,
                iceValid: false,
                
                init() {
                    this.validateAll();
                },
                
                validateNom() {
                    if (!this.form.nom) {
                        this.errors.nom = 'Le nom du fournisseur est obligatoire.';
                    } else if (this.form.nom.length > 255) {
                        this.errors.nom = 'Le nom ne doit pas dépasser 255 caractères.';
                    } else {
                        delete this.errors.nom;
                    }
                },

                validateAdresse() {
                    if (this.form.adresse && this.form.adresse.length > 1000) {
                        this.errors.adresse = 'L\'adresse ne doit pas dépasser 1000 caractères.';
                    } else {
                        delete this.errors.adresse;
                    }
                },
                
                validateEmail() {
                    if (!this.form.email) {
                        delete this.errors.email;
                        this.emailValid = false;
                        return;
                    }
                    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (!emailRegex.test(this.form.email)) {
                        this.errors.email = 'Veuillez saisir une adresse email valide.';
                        this.emailValid = false;
                    } else {
                        delete this.errors.email;
                        this.emailValid = true;
                    }
                },
                
                validateIce() {
                    if (!this.form.ice) {
                        delete this.errors.ice;
                        this.iceValid = false;
                        return;
                    }
                    const iceRegex = /^[A-Z0-9]{15}$/;
                    const iceValue = this.form.ice.toUpperCase();
                    this.form.ice = iceValue;
                    
                    if (!iceRegex.test(iceValue)) {
                        this.errors.ice = 'L\'ICE doit contenir exactement 15 caractères alphanumériques majuscules.';
                        this.iceValid = false;
                    } else {
                        delete this.errors.ice;
                        this.iceValid = true;
                    }
                },
                
                validateTaux() {
                    if (this.form.taux === '' || this.form.taux === null) {
                        delete this.errors.taux;
                        return;
                    }
                    const taux = parseFloat(this.form.taux);
                    if (isNaN(taux)) {
                        this.errors.taux = 'Le taux doit être un nombre.';
                    } else if (taux < 0 || taux > 100) {
                        this.errors.taux = 'Le taux doit être compris entre 0 et 100.';
                    } else {
                        delete this.errors.taux;
                    }
                },
                
                validateVille() {
                    if (this.form.ville && this.form.ville.length > 255) {
                        this.errors.ville = 'La ville ne doit pas dépasser 255 caractères.';
                    } else {
                        delete this.errors.ville;
                    }
                },
                
                validateNomResponsable() {
                    if (this.form.nom_responsable && this.form.nom_responsable.length > 255) {
                        this.errors.nom_responsable = 'Le nom du responsable ne doit pas dépasser 255 caractères.';
                    } else {
                        delete this.errors.nom_responsable;
                    }
                },
                
                validateRib() {
                    if (this.form.rib && this.form.rib.length > 255) {
                        this.errors.rib = 'Le RIB ne doit pas dépasser 255 caractères.';
                    } else {
                        delete this.errors.rib;
                    }
                },
                
                validateAll() {
                    this.validateNom();
                    this.validateAdresse();
                    this.validateEmail();
                    this.validateIce();
                    this.validateTaux();
                    this.validateVille();
                    this.validateNomResponsable();
                    this.validateRib();
                },
                
                get isFormValid() {
                    return Object.keys(this.errors).length === 0 && this.form.nom;
                },
                
                validateAndSubmit() {
                    this.validateAll();
                    
                    if (this.isFormValid) {
                        this.isSubmitting = true;
                        this.$el.submit();
                    } else {
                        const firstError = document.querySelector('.border-red-500');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstError.focus();
                        }
                        alert('Veuillez corriger les erreurs dans le formulaire avant de soumettre.');
                    }
                }
            }
        }
    </script>
    @endpush
@endsection
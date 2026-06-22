{{-- resources/views/missions/create.blade.php avec validation Alpine.js avancée --}}
@extends('layouts.app')

@section('title', 'Créer une Mission')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Créer une nouvelle mission
    </h2>
@endsection

@section('content')
    <div x-data="missionForm()" x-init="init()" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('missions.store') }}" @submit.prevent="validateAndSubmit">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Consultant -->
                    <div>
                        <label for="consultant_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Consultant *
                        </label>
                        <select name="consultant_id" id="consultant_id" x-model="form.consultant_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                :class="errors.consultant_id ? 'border-red-500' : 'border-gray-300'">
                            <option value="">Sélectionner un consultant</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}">{{ $consultant->nom }} - {{ $consultant->email }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.consultant_id" x-text="errors.consultant_id" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Client -->
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Client *
                        </label>
                        <select name="client_id" id="client_id" x-model="form.client_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                :class="errors.client_id ? 'border-red-500' : 'border-gray-300'">
                            <option value="">Sélectionner un client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" data-nom="{{ $client->nom }}">{{ $client->nom }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.client_id" x-text="errors.client_id" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Fournisseur -->
                    <div>
                        <label for="fournisseur_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Fournisseur *
                        </label>
                        <select name="fournisseur_id" id="fournisseur_id" x-model="form.fournisseur_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                :class="errors.fournisseur_id ? 'border-red-500' : 'border-gray-300'">
                            <option value="">Sélectionner un fournisseur</option>
                            @foreach($fournisseurs as $fournisseur)
                                <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.fournisseur_id" x-text="errors.fournisseur_id" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Taux -->
                    <div>
                        <label for="taux" class="block text-sm font-medium text-gray-700 mb-2">
                            Taux (jours) *
                        </label>
                        <input type="number" name="taux" id="taux" x-model="form.taux" step="0.01"
                               @input="validateTaux(); calculateMarge()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.taux ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.taux" x-text="errors.taux" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- TJM -->
                    <div>
                        <label for="tjm" class="block text-sm font-medium text-gray-700 mb-2">
                            TJM (Taux Journalier Moyen) *
                        </label>
                        <input type="number" name="tjm" id="tjm" x-model="form.tjm" step="0.01"
                               @input="validateTjm(); calculateMarge()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.tjm ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.tjm" x-text="errors.tjm" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Prix de vente -->
                    <div>
                        <label for="prix_vente" class="block text-sm font-medium text-gray-700 mb-2">
                            Prix de vente *
                        </label>
                        <input type="number" name="prix_vente" id="prix_vente" x-model="form.prix_vente" step="0.01"
                               @input="validatePrixVente(); calculateMarge()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.prix_vente ? 'border-red-500' : 'border-gray-300'">
                        <div class="mt-1">
                            <p x-show="margeInfo.show" x-html="margeInfo.html" class="text-xs"></p>
                        </div>
                        <p x-show="errors.prix_vente" x-text="errors.prix_vente" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Date début -->
                    <div>
                        <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de début *
                        </label>
                        <input type="date" name="date_debut" id="date_debut" x-model="form.date_debut"
                               @change="validateDates()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.date_debut ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.date_debut" x-text="errors.date_debut" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Date fin -->
                    <div>
                        <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de fin
                        </label>
                        <input type="date" name="date_fin" id="date_fin" x-model="form.date_fin"
                               @change="validateDates()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.date_fin ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.date_fin" x-text="errors.date_fin" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Délai paiement -->
                    <div>
                        <label for="delai_paiement" class="block text-sm font-medium text-gray-700 mb-2">
                            Délai de paiement (jours) *
                        </label>
                        <input type="number" name="delai_paiement" id="delai_paiement" x-model="form.delai_paiement"
                               @input="validateDelaiPaiement()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.delai_paiement ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.delai_paiement" x-text="errors.delai_paiement" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Remarques -->
                    <div class="md:col-span-2">
                        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">
                            Remarques
                        </label>
                        <textarea name="remarques" id="remarques" rows="4" x-model="form.remarques"
                                  @input="validateRemarques()"
                                  class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                  :class="errors.remarques ? 'border-red-500' : 'border-gray-300'"></textarea>
                        <p x-show="errors.remarques" x-text="errors.remarques" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="form.remarques" x-text="form.remarques.length + '/5000 caractères'" class="text-gray-500 text-xs mt-1"></p>
                    </div>
                </div>
                
                <!-- Indicateur de progression -->
                <div class="mt-4" x-show="Object.keys(errors).length > 0">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Veuillez corriger les erreurs avant de soumettre le formulaire.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('missions.index') }}" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting || !isFormValid"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300"
                            :class="{'opacity-50 cursor-not-allowed': !isFormValid}">
                        <span x-show="!isSubmitting">Créer la mission</span>
                        <span x-show="isSubmitting">Création en cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function missionForm() {
            return {
                form: {
                    consultant_id: '{{ old('consultant_id', '') }}',
                    client_id: '{{ old('client_id', '') }}',
                    fournisseur_id: '{{ old('fournisseur_id', '') }}',
                    taux: '{{ old('taux', '') }}',
                    tjm: '{{ old('tjm', '') }}',
                    prix_vente: '{{ old('prix_vente', '') }}',
                    date_debut: '{{ old('date_debut', '') }}',
                    date_fin: '{{ old('date_fin', '') }}',
                    delai_paiement: '{{ old('delai_paiement', 30) }}',
                    remarques: '{{ old('remarques', '') }}'
                },
                errors: {},
                margeInfo: {
                    show: false,
                    html: ''
                },
                isSubmitting: false,
                clientDevise: 'MAD',
                
                init() {
                    // Écouter le changement de client pour récupérer la devise
                    this.$watch('form.client_id', (value) => {
                        const select = document.getElementById('client_id');
                        const selectedOption = select.options[select.selectedIndex];
                        if (selectedOption && selectedOption.dataset.devise) {
                            this.clientDevise = selectedOption.dataset.devise;
                        }
                        this.validateAll();
                    });
                },
                
                // Validation du taux
                validateTaux() {
                    const taux = parseFloat(this.form.taux);
                    if (!this.form.taux) {
                        this.errors.taux = 'Le taux est obligatoire.';
                    } else if (isNaN(taux) || taux <= 0) {
                        this.errors.taux = 'Le taux doit être un nombre positif.';
                    } else if (taux > 1000) {
                        this.errors.taux = 'Le taux ne peut pas dépasser 1000 jours.';
                    } else {
                        delete this.errors.taux;
                    }
                },
                
                // Validation du TJM
                validateTjm() {
                    const tjm = parseFloat(this.form.tjm);
                    if (!this.form.tjm) {
                        this.errors.tjm = 'Le TJM est obligatoire.';
                    } else if (isNaN(tjm) || tjm <= 0) {
                        this.errors.tjm = 'Le TJM doit être un nombre positif.';
                    } else if (tjm > 100000) {
                        this.errors.tjm = 'Le TJM ne peut pas dépasser 100 000.';
                    } else {
                        delete this.errors.tjm;
                    }
                },
                
                // Validation du prix de vente
                validatePrixVente() {
                    const prixVente = parseFloat(this.form.prix_vente);
                    if (!this.form.prix_vente) {
                        this.errors.prix_vente = 'Le prix de vente est obligatoire.';
                    } else if (isNaN(prixVente) || prixVente <= 0) {
                        this.errors.prix_vente = 'Le prix de vente doit être un nombre positif.';
                    } else if (prixVente > 10000000) {
                        this.errors.prix_vente = 'Le prix de vente ne peut pas dépasser 10 000 000.';
                    } else {
                        delete this.errors.prix_vente;
                    }
                },
                
                // Calcul de la marge
                calculateMarge() {
                    const taux = parseFloat(this.form.taux) || 0;
                    const tjm = parseFloat(this.form.tjm) || 0;
                    const prixVente = parseFloat(this.form.prix_vente) || 0;
                    
                    const coutTotal = taux * tjm;
                    const marge = prixVente - coutTotal;
                    const margePourcentage = coutTotal > 0 ? (marge / coutTotal) * 100 : 0;
                    
                    if (prixVente > 0 && coutTotal > 0) {
                        this.margeInfo.show = true;
                        if (marge > 0) {
                            this.margeInfo.html = `<span class="text-green-600">✓ Marge bénéficiaire: ${marge.toFixed(2)} ${this.clientDevise} (${margePourcentage.toFixed(1)}%)</span>`;
                            
                            // Vérifier si la marge est suffisante (plus de 10%)
                            if (margePourcentage < 10) {
                                this.margeInfo.html += `<br><span class="text-yellow-600">⚠ Attention: Marge inférieure à 10%</span>`;
                            }
                        } else {
                            this.margeInfo.html = `<span class="text-red-600">⚠ Marge négative: ${marge.toFixed(2)} ${this.clientDevise} (${margePourcentage.toFixed(1)}%)</span>`;
                        }
                    } else {
                        this.margeInfo.show = false;
                    }
                    
                    // Validation supplémentaire du prix de vente
                    if (prixVente > 0 && coutTotal > 0 && prixVente <= coutTotal) {
                        this.errors.prix_vente = `Le prix de vente doit être supérieur au coût total (${coutTotal.toFixed(2)} ${this.clientDevise}).`;
                    } else if (!this.errors.prix_vente && prixVente > 0) {
                        delete this.errors.prix_vente;
                    }
                },
                
                // Validation des dates
                validateDates() {
                    if (!this.form.date_debut) {
                        this.errors.date_debut = 'La date de début est obligatoire.';
                    } else {
                        const dateDebut = new Date(this.form.date_debut);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        if (dateDebut < today) {
                            this.errors.date_debut = 'La date de début ne peut pas être dans le passé.';
                        } else {
                            delete this.errors.date_debut;
                        }
                        
                        // Validation de la date de fin
                        if (this.form.date_fin) {
                            const dateFin = new Date(this.form.date_fin);
                            if (dateFin < dateDebut) {
                                this.errors.date_fin = 'La date de fin doit être postérieure ou égale à la date de début.';
                            } else {
                                delete this.errors.date_fin;
                            }
                        } else {
                            delete this.errors.date_fin;
                        }
                    }
                },
                
                // Validation du délai de paiement
                validateDelaiPaiement() {
                    const delai = parseInt(this.form.delai_paiement);
                    if (!this.form.delai_paiement && this.form.delai_paiement !== 0) {
                        this.errors.delai_paiement = 'Le délai de paiement est obligatoire.';
                    } else if (isNaN(delai) || delai < 0) {
                        this.errors.delai_paiement = 'Le délai de paiement doit être un nombre positif.';
                    } else if (delai > 365) {
                        this.errors.delai_paiement = 'Le délai de paiement ne peut pas dépasser 365 jours.';
                    } else {
                        delete this.errors.delai_paiement;
                    }
                },
                
                // Validation des remarques
                validateRemarques() {
                    if (this.form.remarques && this.form.remarques.length > 5000) {
                        this.errors.remarques = 'Les remarques ne peuvent pas dépasser 5000 caractères.';
                    } else {
                        delete this.errors.remarques;
                    }
                },
                
                // Validation des sélections
                validateSelections() {
                    if (!this.form.consultant_id) {
                        this.errors.consultant_id = 'Veuillez sélectionner un consultant.';
                    } else {
                        delete this.errors.consultant_id;
                    }
                    
                    if (!this.form.client_id) {
                        this.errors.client_id = 'Veuillez sélectionner un client.';
                    } else {
                        delete this.errors.client_id;
                    }
                    
                    if (!this.form.fournisseur_id) {
                        this.errors.fournisseur_id = 'Veuillez sélectionner un fournisseur.';
                    } else {
                        delete this.errors.fournisseur_id;
                    }
                },
                
                // Validation de tous les champs
                validateAll() {
                    this.validateSelections();
                    this.validateTaux();
                    this.validateTjm();
                    this.validatePrixVente();
                    this.validateDates();
                    this.validateDelaiPaiement();
                    this.validateRemarques();
                    this.calculateMarge();
                },
                
                // Vérifier si le formulaire est valide
                get isFormValid() {
                    return Object.keys(this.errors).length === 0 &&
                           this.form.consultant_id &&
                           this.form.client_id &&
                           this.form.fournisseur_id &&
                           this.form.taux &&
                           this.form.tjm &&
                           this.form.prix_vente &&
                           this.form.date_debut;
                },
                
                // Soumettre le formulaire avec validation
                validateAndSubmit() {
                    this.validateAll();
                    
                    if (this.isFormValid) {
                        this.isSubmitting = true;
                        this.$el.submit();
                    } else {
                        // Faire défiler jusqu'au premier erreur
                        const firstError = document.querySelector('.border-red-500');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstError.focus();
                        }
                        
                        // Afficher une notification
                        alert('Veuillez corriger les erreurs dans le formulaire avant de soumettre.');
                    }
                }
            }
        }
    </script>
    @endpush
@endsection
{{-- resources/views/clients/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Créer un Client')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Créer un nouveau client
    </h2>
@endsection

@section('content')
    <div x-data="clientForm()" x-init="init()" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('clients.store') }}" @submit.prevent="validateAndSubmit">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nom du client -->
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom du client *
                        </label>
                        <input type="text" name="nom" id="nom" x-model="form.nom"
                               @input="validateNom()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.nom ? 'border-red-500' : 'border-gray-300'"
                               placeholder="Nom de l'entreprise">
                        <p x-show="errors.nom" x-text="errors.nom" class="text-red-500 text-xs mt-1"></p>
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
                    
                    <!-- ICE -->
                    <div>
                        <label for="ice" class="block text-sm font-medium text-gray-700 mb-2">
                            ICE (15 caractères) *
                        </label>
                        <input type="text" name="ice" id="ice" x-model="form.ice"
                               @input="validateIce()"
                               @blur="checkIceUniqueness()"
                               maxlength="15"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 uppercase"
                               :class="errors.ice ? 'border-red-500' : 'border-gray-300'"
                               placeholder="123456789012345">
                        <p x-show="errors.ice" x-text="errors.ice" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="iceValid && !errors.ice && form.ice" class="text-green-500 text-xs mt-1">✓ Format ICE valide</p>
                        <p x-show="iceChecking" class="text-blue-500 text-xs mt-1">Vérification de l'unicité...</p>
                        <p x-show="iceUnique === false && !errors.ice" class="text-red-500 text-xs mt-1">⚠ Cet ICE est déjà utilisé.</p>
                    </div>
                    
                    <!-- TVA -->
                    <div>
                        <label for="tva" class="block text-sm font-medium text-gray-700 mb-2">
                            TVA (%) *
                        </label>
                        <input type="number" name="tva" id="tva" x-model="form.tva" step="0.01"
                               @input="validateTva()"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.tva ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.tva" x-text="errors.tva" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Devise -->
                    <div>
                        <label for="devise" class="block text-sm font-medium text-gray-700 mb-2">
                            Devise *
                        </label>
                        <select name="devise" id="devise" x-model="form.devise"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            <option value="MAD">MAD - Dirham Marocain</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="USD">USD - Dollar US</option>
                            <option value="GBP">GBP - Livre Sterling</option>
                            <option value="CAD">CAD - Dollar Canadien</option>
                        </select>
                    </div>
                    
                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Statut *
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
                    <a href="{{ route('clients.index') }}" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Annuler
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting || !isFormValid"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300"
                            :class="{'opacity-50 cursor-not-allowed': !isFormValid}">
                        <span x-show="!isSubmitting">Créer le client</span>
                        <span x-show="isSubmitting">Création en cours...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function clientForm() {
            return {
                form: {
                    nom: '{{ old('nom', '') }}',
                    adresse: '{{ old('adresse', '') }}',
                    email: '{{ old('email', '') }}',
                    ice: '{{ old('ice', '') }}',
                    tva: '{{ old('tva', 20) }}',
                    devise: '{{ old('devise', 'MAD') }}',
                    statut: '{{ old('statut', '1') }}'
                },
                errors: {},
                isSubmitting: false,
                emailValid: false,
                iceValid: false,
                iceUnique: null,
                iceChecking: false,
                
                init() {
                    this.validateAll();
                },
                
                validateNom() {
                    const nomRegex = /^[a-zA-ZÀ-ÿ\s\-\']+$/;
                    if (!this.form.nom) {
                        this.errors.nom = 'Le nom du client est obligatoire.';
                    } else if (this.form.nom.length < 2) {
                        this.errors.nom = 'Le nom doit contenir au moins 2 caractères.';
                    } else if (this.form.nom.length > 100) {
                        this.errors.nom = 'Le nom ne doit pas dépasser 100 caractères.';
                    } else if (!nomRegex.test(this.form.nom)) {
                        this.errors.nom = 'Le nom ne doit contenir que des lettres, espaces, tirets et apostrophes.';
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
                    const iceRegex = /^[A-Z0-9]{15}$/;
                    const iceValue = this.form.ice.toUpperCase();
                    this.form.ice = iceValue;
                    
                    if (!this.form.ice) {
                        this.errors.ice = 'L\'ICE est obligatoire.';
                        this.iceValid = false;
                    } else if (!iceRegex.test(iceValue)) {
                        this.errors.ice = 'L\'ICE doit contenir exactement 15 caractères alphanumériques majuscules.';
                        this.iceValid = false;
                    } else {
                        delete this.errors.ice;
                        this.iceValid = true;
                    }
                },
                
                async checkIceUniqueness() {
                    if (!this.iceValid) return;
                    
                    this.iceChecking = true;
                    try {
                        const response = await fetch(`{{ route('clients.check-ice') }}?ice=${this.form.ice}&id={{ isset($client) ? $client->id : '' }}`);
                        const data = await response.json();
                        this.iceUnique = data.unique;
                        if (!data.unique) {
                            this.errors.ice = 'Cet ICE est déjà utilisé par un autre client.';
                        } else if (this.iceValid) {
                            delete this.errors.ice;
                        }
                    } catch (error) {
                        console.error('Erreur lors de la vérification ICE:', error);
                    } finally {
                        this.iceChecking = false;
                    }
                },
                
                validateTva() {
                    const tva = parseFloat(this.form.tva);
                    if (this.form.tva === '' || isNaN(tva)) {
                        this.errors.tva = 'Le taux de TVA est obligatoire.';
                    } else if (tva < 0 || tva > 100) {
                        this.errors.tva = 'Le taux de TVA doit être compris entre 0 et 100.';
                    } else {
                        delete this.errors.tva;
                    }
                },
                
                validateAll() {
                    this.validateNom();
                    this.validateAdresse();
                    this.validateEmail();
                    this.validateIce();
                    this.validateTva();
                },
                
                get isFormValid() {
                    return Object.keys(this.errors).length === 0 &&
                           this.form.nom &&
                           this.iceValid &&
                           this.iceUnique !== false &&
                           this.form.tva !== '';
                },
                
                async validateAndSubmit() {
                    this.validateAll();
                    await this.checkIceUniqueness();
                    
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
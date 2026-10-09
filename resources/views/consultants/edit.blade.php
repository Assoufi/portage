{{-- resources/views/consultants/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Modifier le Consultant')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier le consultant : {{ $consultant->nom }}
    </h2>
@endsection

@section('content')
    <div x-data="consultantForm()" x-init="init()" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('consultants.update', $consultant) }}" @submit.prevent="validateAndSubmit">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nom -->
                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom complet *
                        </label>
                        <input type="text" name="nom" id="nom" x-model="form.nom"
                               @input="validateNom()"
                               value="{{ old('nom', $consultant->nom) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.nom ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.nom" x-text="errors.nom" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- CIN -->
                    <div>
                        <label for="cin" class="block text-sm font-medium text-gray-700 mb-2">
                            CIN
                        </label>
                        <input type="text" name="cin" id="cin" x-model="form.cin"
                               @input="validateCin()"
                               value="{{ old('cin', $consultant->cin) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.cin ? 'border-red-500' : 'border-gray-300'"
                               placeholder="AB123456">
                        <p x-show="errors.cin" x-text="errors.cin" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email *
                        </label>
                        <input type="email" name="email" id="email" x-model="form.email"
                               @input="validateEmail()"
                               value="{{ old('email', $consultant->email) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.email ? 'border-red-500' : 'border-gray-300'">
                        <p x-show="errors.email" x-text="errors.email" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="emailValid && !errors.email" class="text-green-500 text-xs mt-1">✓ Email valide</p>
                    </div>
                    
                    <!-- Téléphone -->
                    <div>
                        <label for="tel" class="block text-sm font-medium text-gray-700 mb-2">
                            Téléphone *
                        </label>
                        <input type="tel" name="tel" id="tel" x-model="form.tel"
                               @input="validateTel()"
                               value="{{ old('tel', $consultant->tel) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.tel ? 'border-red-500' : 'border-gray-300'"
                               placeholder="+212 6XX XXX XXX">
                        <p x-show="errors.tel" x-text="errors.tel" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Fonction -->
                    <div>
                        <label for="fonction" class="block text-sm font-medium text-gray-700 mb-2">
                            Fonction
                        </label>
                        <input type="text" name="fonction" id="fonction" x-model="form.fonction"
                               @input="validateFonction()"
                               value="{{ old('fonction', $consultant->fonction) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.fonction ? 'border-red-500' : 'border-gray-300'"
                               placeholder="Développeur, Comptable...">
                        <p x-show="errors.fonction" x-text="errors.fonction" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- RIB -->
                    <div>
                        <label for="rib" class="block text-sm font-medium text-gray-700 mb-2">
                            RIB
                        </label>
                        <input type="text" name="rib" id="rib" x-model="form.rib"
                               @input="validateRib()"
                               value="{{ old('rib', $consultant->rib) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.rib ? 'border-red-500' : 'border-gray-300'"
                               placeholder="FR76 1234 5678 9012 3456 7890 123">
                        <p x-show="errors.rib" x-text="errors.rib" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="form.rib && !errors.rib" class="text-green-500 text-xs mt-1">✓ Format RIB valide</p>
                    </div>
                    
                    <!-- Mode de paiement -->
                    <div>
                        <label for="mode_paiement" class="block text-sm font-medium text-gray-700 mb-2">
                            Mode de paiement *
                        </label>
                        <select name="mode_paiement" id="mode_paiement" x-model="form.mode_paiement"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            <option value="virement" {{ old('mode_paiement', $consultant->mode_paiement->value ?? 'virement') == 'virement' ? 'selected' : '' }}>Virement bancaire</option>
                            <option value="cheque" {{ old('mode_paiement', $consultant->mode_paiement->value ?? 'virement') == 'cheque' ? 'selected' : '' }}>Chèque</option>
                            <option value="especes" {{ old('mode_paiement', $consultant->mode_paiement->value ?? 'virement') == 'especes' ? 'selected' : '' }}>Espèces</option>
                        </select>
                    </div>
                    
                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Statut *
                        </label>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="statut" value="1" x-model="form.statut"
                                       {{ old('statut', $consultant->statut ? '1' : '0') == '1' ? 'checked' : '' }}
                                       class="form-radio text-blue-600">
                                <span class="ml-2">Actif</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="statut" value="0" x-model="form.statut"
                                       {{ old('statut', $consultant->statut ? '1' : '0') == '0' ? 'checked' : '' }}
                                       class="form-radio text-red-600">
                                <span class="ml-2">Inactif</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('consultants.index') }}" 
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
        function consultantForm() {
            return {
                form: {
                    nom: '{{ old('nom', $consultant->nom) }}',
                    cin: '{{ old('cin', $consultant->cin) }}',
                    fonction: '{{ old('fonction', $consultant->fonction) }}',
                    email: '{{ old('email', $consultant->email) }}',
                    tel: '{{ old('tel', $consultant->tel) }}',
                    rib: '{{ old('rib', $consultant->rib) }}',
                    mode_paiement: '{{ old('mode_paiement', $consultant->mode_paiement->value ?? 'virement') }}',
                    statut: '{{ old('statut', $consultant->statut ? '1' : '0') }}'
                },
                errors: {},
                isSubmitting: false,
                emailValid: false,
                
                init() {
                    this.validateAll();
                },
                
                validateNom() {
                    const nomRegex = /^[a-zA-ZÀ-ÿ\s\-\']+$/;
                    if (!this.form.nom) {
                        this.errors.nom = 'Le nom est obligatoire.';
                    } else if (this.form.nom.length < 2) {
                        this.errors.nom = 'Le nom doit contenir au moins 2 caractères.';
                    } else if (this.form.nom.length > 255) {
                        this.errors.nom = 'Le nom ne doit pas dépasser 255 caractères.';
                    } else if (!nomRegex.test(this.form.nom)) {
                        this.errors.nom = 'Le nom ne doit contenir que des lettres, espaces, tirets et apostrophes.';
                    } else {
                        delete this.errors.nom;
                    }
                },
                
                validateEmail() {
                    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                    if (!this.form.email) {
                        this.errors.email = 'L\'email est obligatoire.';
                        this.emailValid = false;
                    } else if (!emailRegex.test(this.form.email)) {
                        this.errors.email = 'Veuillez saisir une adresse email valide.';
                        this.emailValid = false;
                    } else if (this.form.email.length > 255) {
                        this.errors.email = 'L\'email ne doit pas dépasser 255 caractères.';
                        this.emailValid = false;
                    } else {
                        delete this.errors.email;
                        this.emailValid = true;
                    }
                },
                
                validateTel() {
                    const telRegex = /^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,4}$/;
                    if (!this.form.tel) {
                        this.errors.tel = 'Le numéro de téléphone est obligatoire.';
                    } else if (!telRegex.test(this.form.tel)) {
                        this.errors.tel = 'Veuillez saisir un numéro de téléphone valide.';
                    } else {
                        delete this.errors.tel;
                    }
                },
                
                validateRib() {
                    if (this.form.rib) {
                        const ribRegex = /^[A-Z0-9]{10,50}$/;
                        if (!ribRegex.test(this.form.rib.toUpperCase())) {
                            this.errors.rib = 'Le RIB doit contenir uniquement des lettres majuscules et des chiffres (10-50 caractères).';
                        } else {
                            delete this.errors.rib;
                        }
                    } else {
                        delete this.errors.rib;
                    }
                },
                
                validateCin() {
                    if (this.form.cin) {
                        const cinRegex = /^[A-Za-z0-9\-]+$/;
                        if (!cinRegex.test(this.form.cin)) {
                            this.errors.cin = 'Le CIN ne doit contenir que des lettres, des chiffres ou des tirets.';
                        } else if (this.form.cin.length > 30) {
                            this.errors.cin = 'Le CIN ne doit pas dépasser 30 caractères.';
                        } else {
                            delete this.errors.cin;
                        }
                    } else {
                        delete this.errors.cin;
                    }
                },
                
                validateFonction() {
                    if (this.form.fonction) {
                        if (this.form.fonction.length > 50) {
                            this.errors.fonction = 'La fonction ne doit pas dépasser 50 caractères.';
                        } else {
                            delete this.errors.fonction;
                        }
                    } else {
                        delete this.errors.fonction;
                    }
                },
                
                validateAll() {
                    this.validateNom();
                    this.validateCin();
                    this.validateFonction();
                    this.validateEmail();
                    this.validateTel();
                    this.validateRib();
                },
                
                get isFormValid() {
                    return Object.keys(this.errors).length === 0 &&
                           this.form.nom &&
                           this.emailValid &&
                           this.form.tel;
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
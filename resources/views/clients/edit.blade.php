{{-- resources/views/clients/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Modifier le Client')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier le client : {{ $client->nom }}
    </h2>
@endsection

@section('content')
    <div x-data="clientForm()" x-init="init()" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('clients.update', $client) }}" @submit.prevent="validateAndSubmit">
                @csrf
                @method('PUT')
                
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
                                  :class="errors.adresse ? 'border-red-500' : 'border-gray-300'">{{ old('adresse', $client->adresse) }}</textarea>
                        <p x-show="errors.adresse" x-text="errors.adresse" class="text-red-500 text-xs mt-1"></p>
                    </div>
                    
                    <!-- Adresse de facturation -->
                    <div class="md:col-span-2">
                        <label for="adresse_facturation" class="block text-sm font-medium text-gray-700 mb-2">
                            Adresse de facturation
                        </label>
                        <textarea name="adresse_facturation" id="adresse_facturation" rows="3" maxlength="1000" x-model="form.adresse_facturation"
                                  class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                  :class="errors.adresse_facturation ? 'border-red-500' : 'border-gray-300'">{{ old('adresse_facturation', $client->adresse_facturation) }}</textarea>
                    </div>
                    
                    <!-- Type d'identification -->
                    <div>
                        <label for="type_identification" class="block text-sm font-medium text-gray-700 mb-2">
                            Type d'identification
                        </label>
                        <input type="text" name="type_identification" id="type_identification" x-model="form.type_identification"
                               maxlength="50"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               placeholder="ICE, IF, RC...">
                    </div>
                    
                    <!-- Numéro d'identification -->
                    <div>
                        <label for="num_identification" class="block text-sm font-medium text-gray-700 mb-2">
                            Numéro d'identification
                        </label>
                        <input type="text" name="num_identification" id="num_identification" x-model="form.num_identification"
                               @input="form.num_identification = form.num_identification.toUpperCase()"
                               @blur="checkNumIdentification()"
                               maxlength="50"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.num_identification ? 'border-red-500' : 'border-gray-300'
                               placeholder="123456789012345">
                        <p x-show="errors.num_identification" x-text="errors.num_identification" class="text-red-500 text-xs mt-1"></p>
                        <p x-show="numChecking" class="text-blue-500 text-xs mt-1">Vérification de l'unicité...</p>
                        <p x-show="numUnique === false && !errors.num_identification" class="text-red-500 text-xs mt-1">⚠ Ce numéro est déjà utilisé.</p>
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
                    
                    <!-- Délai de paiement -->
                    <div>
                        <label for="delai_paiement" class="block text-sm font-medium text-gray-700 mb-2">
                            Délai de paiement (jours)
                        </label>
                        <input type="number" name="delai_paiement" id="delai_paiement" x-model="form.delai_paiement" min="0" max="365"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                               :class="errors.delai_paiement ? 'border-red-500' : 'border-gray-300'"
                               placeholder="Ex : 30">
                    </div>
                    
                    <!-- Téléphone -->
                    <div>
                        <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">
                            Téléphone
                        </label>
                        <input type="text" name="telephone" id="telephone" maxlength="20" x-model="form.telephone"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('telephone') border-red-500 @enderror"
                               placeholder="+212 6 00 00 00 00">
                        @error('telephone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Périodicité -->
                    <div>
                        <label for="periodicite" class="block text-sm font-medium text-gray-700 mb-2">
                            Périodicité
                        </label>
                        <select name="periodicite" id="periodicite" x-model="form.periodicite"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('periodicite') border-red-500 @enderror">
                            <option value="">— Aucune —</option>
                            <option value="Mensuelle">Mensuelle</option>
                            <option value="Occasionnelle">Occasionnelle</option>
                        </select>
                        @error('periodicite')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Mode de livraison -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mode de livraison
                        </label>
                        <div class="flex flex-wrap items-center gap-4">
                            @foreach(['Papier', 'Email', 'Whatsapp'] as $mode)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="mode_livraison[]" value="{{ $mode }}"
                                           x-model="form.mode_livraison"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring focus:ring-blue-200 @error('mode_livraison') border-red-500 @enderror">
                                    <span class="ml-2">{{ $mode }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('mode_livraison')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        @error('mode_livraison.*')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Notifier à -->
                    <div class="md:col-span-2">
                        <label for="notifyto" class="block text-sm font-medium text-gray-700 mb-2">
                            Notifier à
                        </label>
                        <input type="text" name="notifyto" id="notifyto" maxlength="250" x-model="form.notifyto"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('notifyto') border-red-500 @enderror"
                               placeholder="email1@exemple.ma; email2@exemple.ma">
                        <p class="text-xs text-gray-500 mt-1">Plusieurs adresses possibles, séparées par « ; ».</p>
                        @error('notifyto')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Copie notification -->
                    <div class="md:col-span-2">
                        <label for="notifycc" class="block text-sm font-medium text-gray-700 mb-2">
                            Copie notification
                        </label>
                        <input type="text" name="notifycc" id="notifycc" maxlength="250" x-model="form.notifycc"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('notifycc') border-red-500 @enderror"
                               placeholder="copie1@exemple.ma; copie2@exemple.ma">
                        <p class="text-xs text-gray-500 mt-1">Plusieurs adresses possibles, séparées par « ; ».</p>
                        @error('notifycc')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
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
                    
                    <!-- Remarques -->
                    <div class="md:col-span-2">
                        <label for="remarques" class="block text-sm font-medium text-gray-700 mb-2">
                            Remarques
                        </label>
                        <textarea name="remarques" id="remarques" rows="3" maxlength="2000" x-model="form.remarques"
                                  class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                                  :class="errors.remarques ? 'border-red-500' : 'border-gray-300'">{{ old('remarques', $client->remarques) }}</textarea>
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
                        <span x-show="!isSubmitting">Mettre à jour</span>
                        <span x-show="isSubmitting">Mise à jour en cours...</span>
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
                    nom: '{{ old('nom', $client->nom) }}',
                    adresse: '{{ old('adresse', $client->adresse) }}',
                    email: '{{ old('email', $client->email) }}',
                    type_identification: '{{ old('type_identification', $client->type_identification) }}',
                    num_identification: '{{ old('num_identification', $client->num_identification) }}',
                    tva: '{{ old('tva', $client->tva) }}',
                    devise: '{{ old('devise', $client->devise) }}',
                    statut: '{{ old('statut', $client->statut ? '1' : '0') }}',
                    adresse_facturation: '{{ old('adresse_facturation', $client->adresse_facturation) }}',
                    delai_paiement: '{{ old('delai_paiement', $client->delai_paiement) }}',
                    remarques: '{{ old('remarques', $client->remarques) }}',
                    periodicite: '{{ old('periodicite', $client->periodicite) }}',
                    mode_livraison: @js(old('mode_livraison', $client->mode_livraison ?? [])),
                    telephone: '{{ old('telephone', $client->telephone) }}',
                    notifyto: '{{ old('notifyto', $client->notifyto) }}',
                    notifycc: '{{ old('notifycc', $client->notifycc) }}'
                },
                errors: {},
                isSubmitting: false,
                emailValid: false,
                numUnique: null,
                numChecking: false,
                
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
                
                async checkNumIdentification() {
                    if (!this.form.num_identification) {
                        this.numUnique = null;
                        return;
                    }
                    
                    this.numChecking = true;
                    try {
                        const response = await fetch(`{{ route('clients.check-identification') }}?num_identification=${encodeURIComponent(this.form.num_identification)}&id={{ $client->id }}`);
                        const data = await response.json();
                        this.numUnique = data.unique;
                        if (!data.unique) {
                            this.errors.num_identification = 'Ce numéro d\'identification est déjà utilisé par un autre client.';
                        } else {
                            delete this.errors.num_identification;
                        }
                    } catch (error) {
                        console.error('Erreur lors de la vérification du numéro d\'identification:', error);
                    } finally {
                        this.numChecking = false;
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
                    this.validateTva();
                },
                
                get isFormValid() {
                    return Object.keys(this.errors).length === 0 &&
                           this.form.nom &&
                           this.numUnique !== false &&
                           this.form.tva !== '';
                },
                
                async validateAndSubmit() {
                    this.validateAll();
                    await this.checkNumIdentification();
                    
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
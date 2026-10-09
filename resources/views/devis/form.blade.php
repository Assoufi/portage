@push('scripts')
    <script>
        window.devisConfig = @js([
            'missionId' => old('mission_id', $devis->mission_id),
            'clientId' => old('client_id', $devis->client_id),
            'fournisseurId' => old('fournisseur_id', $devis->fournisseur_id),
            'numero' => old('numero_devis', $devis->numero_devis ?? $numero ?? ''),
            'dateDevis' => old('date_devis', $devis->date_devis?->format('Y-m-d') ?? date('Y-m-d')),
            'description' => old('description', $devis->description),
            'quantite' => old('quantite', $devis->quantite ?? 1),
            'prixUnitaire' => old('prix_unitaire', $devis->prix_unitaire ?? ''),
            'missions' => $missionsData->all(),
        ]);

        function devisForm(config) {
            return {
                mission_id: config.missionId || '',
                client_id: config.clientId || '',
                fournisseur_id: config.fournisseurId || '',
                numero_devis: config.numero || '',
                date_devis: config.dateDevis || '',
                description: config.description || '',
                quantite: config.quantite || 1,
                prix_unitaire: config.prixUnitaire || '',
                missions: config.missions || [],

                get totalHt() {
                    return (parseFloat(this.quantite) || 0) * (parseFloat(this.prix_unitaire) || 0);
                },

                get totalHtFormate() {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(this.totalHt);
                },

                onMissionChange(event) {
                    this.mission_id = event.target.value;

                    const mission = this.missions.find(
                        item => String(item.id) === String(this.mission_id)
                    );
                    if (!mission) {
                        return;
                    }

                    this.client_id = mission.client_id || '';
                    this.fournisseur_id = mission.fournisseur_id || '';

                    if (!this.prix_unitaire) {
                        this.prix_unitaire = mission.prix_vente || '';
                    }

                    if (!this.description && mission.titre) {
                        this.description = mission.titre;
                    }
                },
            };
        }
    </script>
@endpush

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Mission (pré-remplit client, fournisseur, prix) -->
    <div class="md:col-span-2">
        <label for="mission_id" class="block text-sm font-medium text-gray-700 mb-2">Mission</label>
        <select name="mission_id" id="mission_id"
                x-model="mission_id"
                @change="onMissionChange($event)"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mission_id') border-red-500 @enderror">
            <option value="">Sélectionner une mission...</option>
            @foreach($missions as $mission)
                <option value="{{ $mission->id }}" {{ old('mission_id', $devis->mission_id) == $mission->id ? 'selected' : '' }}>
                    {{ $mission->titre ?? 'Mission #'.$mission->id }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">
            La sélection d'une mission pré-remplit le client, le fournisseur et le prix unitaire.
        </p>
        @error('mission_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Client -->
    <div>
        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
        <select name="client_id" id="client_id"
                x-model="client_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('client_id') border-red-500 @enderror"
                required>
            <option value="">Sélectionner un client...</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ old('client_id', $devis->client_id) == $client->id ? 'selected' : '' }}>
                    {{ $client->nom }}
                </option>
            @endforeach
        </select>
        @error('client_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Fournisseur -->
    <div>
        <label for="fournisseur_id" class="block text-sm font-medium text-gray-700 mb-2">Fournisseur *</label>
        <select name="fournisseur_id" id="fournisseur_id"
                x-model="fournisseur_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('fournisseur_id') border-red-500 @enderror"
                required>
            <option value="">Sélectionner un fournisseur...</option>
            @foreach($fournisseurs as $fournisseur)
                <option value="{{ $fournisseur->id }}" {{ old('fournisseur_id', $devis->fournisseur_id) == $fournisseur->id ? 'selected' : '' }}>
                    {{ $fournisseur->nom }}
                </option>
            @endforeach
        </select>
        @error('fournisseur_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Numéro de devis -->
    <div>
        <label for="numero_devis" class="block text-sm font-medium text-gray-700 mb-2">N° Devis *</label>
        <input type="text" name="numero_devis" id="numero_devis" maxlength="50"
               x-model="numero_devis"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('numero_devis') border-red-500 @enderror"
               required>
        @error('numero_devis')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Date -->
    <div>
        <label for="date_devis" class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
        <input type="date" name="date_devis" id="date_devis"
               x-model="date_devis"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_devis') border-red-500 @enderror"
               required>
        @error('date_devis')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Description -->
    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
        <textarea name="description" id="description" rows="3"
                  x-model="description"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('description') border-red-500 @enderror"></textarea>
        @error('description')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Quantité -->
    <div>
        <label for="quantite" class="block text-sm font-medium text-gray-700 mb-2">Quantité *</label>
        <input type="number" step="0.01" min="0.01" name="quantite" id="quantite"
               x-model="quantite"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('quantite') border-red-500 @enderror"
               required>
        @error('quantite')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Prix unitaire -->
    <div>
        <label for="prix_unitaire" class="block text-sm font-medium text-gray-700 mb-2">Prix unitaire HT *</label>
        <input type="number" step="0.01" min="0" name="prix_unitaire" id="prix_unitaire"
               x-model="prix_unitaire"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('prix_unitaire') border-red-500 @enderror"
               required>
        @error('prix_unitaire')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Total HT (calculé) -->
    <div class="md:col-span-2">
        <div class="flex items-center justify-end space-x-4 bg-gray-50 rounded-md px-4 py-3">
            <span class="text-sm font-semibold text-gray-700">Total HT</span>
            <span class="text-lg font-bold text-gray-900" x-text="totalHtFormate"></span>
        </div>
    </div>
</div>

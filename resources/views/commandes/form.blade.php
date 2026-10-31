@push('scripts')
    <script>
        window.commandeConfig = @js([
            'missionId' => old('mission_id', $commande->mission_id),
            'fournisseur' => '',
            'numCommande' => old('num_commande', $commande->num_commande),
            'dateCommande' => old('date_commande', $commande->date_commande?->format('Y-m-d') ?? date('Y-m-d')),
            'dateDebut' => old('date_debut', $commande->date_debut?->format('Y-m-d')),
            'dateFin' => old('date_fin', $commande->date_fin?->format('Y-m-d')),
            'quantite' => old('quantite', $commande->quantite ?? 1),
            'tjm' => old('tjm', $commande->tjm ?? ''),
            'statut' => old('statut', $commande->statut ?? 'ACTIF'),
            'missions' => $missionsData->all(),
        ]);

        function commandeForm(config) {
            return {
                mission_id: config.missionId || '',
                fournisseur: config.fournisseur || '',
                num_commande: config.numCommande || '',
                date_commande: config.dateCommande || '',
                date_debut: config.dateDebut || '',
                date_fin: config.dateFin || '',
                quantite: config.quantite || 1,
                tjm: config.tjm || '',
                statut: config.statut || 'ACTIF',
                missions: config.missions || [],

                init() {
                    if (this.mission_id && !this.fournisseur) {
                        this.onMissionChange({ target: { value: this.mission_id } });
                    }
                },

                get montantHt() {
                    return (parseFloat(this.quantite) || 0) * (parseFloat(this.tjm) || 0);
                },

                get montantHtFormate() {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(this.montantHt);
                },

                onMissionChange(event) {
                    this.mission_id = event.target.value;

                    const mission = this.missions.find(
                        item => String(item.id) === String(this.mission_id)
                    );
                    if (!mission) {
                        this.fournisseur = '';
                        return;
                    }

                    // Le fournisseur est dérivé de la mission sélectionnée.
                    this.fournisseur = mission.fournisseur || '';

                    if (!this.tjm) {
                        this.tjm = mission.tjm || '';
                    }
                },
            };
        }
    </script>
@endpush

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Mission (pré-remplit fournisseur, TJM) -->
    <div class="md:col-span-2">
        <label for="mission_id" class="block text-sm font-medium text-gray-700 mb-2">Mission *</label>
        <select name="mission_id" id="mission_id"
                x-model="mission_id"
                @change="onMissionChange($event)"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mission_id') border-red-500 @enderror"
                required>
            <option value="">Sélectionner une mission...</option>
            @foreach($missions as $mission)
                <option value="{{ $mission->id }}" {{ old('mission_id', $commande->mission_id) == $mission->id ? 'selected' : '' }}>
                    {{ $mission->titre ?? 'Mission #'.$mission->id }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">
            Seules les missions en cours sont proposées. Le fournisseur et le TJM sont dérivés de la mission.
        </p>
        @error('mission_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Fournisseur (dérivé de la mission, lecture seule) -->
    <div>
        <label for="fournisseur" class="block text-sm font-medium text-gray-700 mb-2">Fournisseur</label>
        <input type="text" id="fournisseur" value="" x-model="fournisseur" disabled
               class="w-full rounded-md border-gray-200 bg-gray-100 shadow-sm text-gray-500">
        <p class="text-xs text-gray-500 mt-1">
            Défini automatiquement par la mission sélectionnée.
        </p>
    </div>

    <!-- Numéro de commande -->
    <div>
        <label for="num_commande" class="block text-sm font-medium text-gray-700 mb-2">N° Commande *</label>
        <input type="text" name="num_commande" id="num_commande" maxlength="50"
               x-model="num_commande"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('num_commande') border-red-500 @enderror"
               required>
        @error('num_commande')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Date commande -->
    <div>
        <label for="date_commande" class="block text-sm font-medium text-gray-700 mb-2">Date commande *</label>
        <input type="date" name="date_commande" id="date_commande"
               x-model="date_commande"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_commande') border-red-500 @enderror"
               required>
        @error('date_commande')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Date début -->
    <div>
        <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
        <input type="date" name="date_debut" id="date_debut"
               x-model="date_debut"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_debut') border-red-500 @enderror">
        @error('date_debut')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Date fin -->
    <div>
        <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
        <input type="date" name="date_fin" id="date_fin"
               x-model="date_fin"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_fin') border-red-500 @enderror">
        @error('date_fin')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Statut -->
    <div>
        <label for="statut" class="block text-sm font-medium text-gray-700 mb-2">Statut *</label>
        <select name="statut" id="statut"
                x-model="statut"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('statut') border-red-500 @enderror"
                required>
            @foreach(\App\Models\Commande::STATUTS as $valeur => $libelle)
                <option value="{{ $valeur }}" {{ old('statut', $commande->statut ?? 'ACTIF') === $valeur ? 'selected' : '' }}>
                    {{ $libelle }}
                </option>
            @endforeach
        </select>
        @error('statut')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Quantité -->
    <div>
        <label for="quantite" class="block text-sm font-medium text-gray-700 mb-2">Quantité (jours) *</label>
        <input type="number" step="0.01" min="0.01" name="quantite" id="quantite"
               x-model="quantite"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('quantite') border-red-500 @enderror"
               required>
        @error('quantite')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- TJM -->
    <div>
        <label for="tjm" class="block text-sm font-medium text-gray-700 mb-2">TJM *</label>
        <input type="number" step="0.01" min="0" name="tjm" id="tjm"
               x-model="tjm"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('tjm') border-red-500 @enderror"
               required>
        @error('tjm')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Montant HT (calculé) -->
    <div class="md:col-span-2">
        <div class="flex items-center justify-end space-x-4 bg-gray-50 rounded-md px-4 py-3">
            <span class="text-sm font-semibold text-gray-700">Montant HT</span>
            <span class="text-lg font-bold text-gray-900" x-text="montantHtFormate"></span>
        </div>
    </div>
</div>

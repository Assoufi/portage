{{-- resources/views/attestations/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Modifier l\'Attestation')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Modifier l'attestation #{{ $attestation->id }}
    </h2>
@endsection

@section('content')
    @push('scripts')
        <script>
            window.attestationConfig = @js([
                'missions' => $missionsData->all(),
                'consultantId' => old('consultant_id', $attestation->consultant_id),
                'missionId' => old('mission_id', $attestation->mission_id),
                'client' => old('client', $attestation->client),
                'fonction' => old('fonction', $attestation->fonction),
                'dateDebut' => old('date_debut', $attestation->date_debut?->format('Y-m-d')),
                'dateFin' => old('date_fin', $attestation->date_fin?->format('Y-m-d')),
            ]);

            function attestationForm(config) {
                return {
                    consultant_id: config.consultantId || '',
                    mission_id: config.missionId || '',
                    client: config.client || '',
                    fonction: config.fonction || '',
                    date_debut: config.dateDebut || '',
                    date_fin: config.dateFin || '',
                    missions: config.missions || [],

                    get missionsFiltrees() {
                        if (!this.consultant_id) {
                            return [];
                        }

                        return this.missions.filter(
                            mission => String(mission.consultant_id) === String(this.consultant_id)
                        );
                    },

                    refreshMissionOptions() {
                        const select = this.$refs.missionSelect;
                        if (!select) {
                            return;
                        }

                        select.innerHTML = '';

                        const placeholder = document.createElement('option');
                        placeholder.value = '';
                        placeholder.textContent = 'Sélectionner une mission...';
                        select.appendChild(placeholder);

                        this.missionsFiltrees.forEach(mission => {
                            const option = document.createElement('option');
                            option.value = mission.id;
                            option.textContent = mission.titre;
                            if (String(mission.id) === String(this.mission_id)) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    },

                    onConsultantChange(event) {
                        this.consultant_id = event.target.value;
                        this.mission_id = '';
                        this.client = '';
                        this.date_debut = '';
                        this.date_fin = '';

                        if (!this.fonction) {
                            const option = event.target.selectedOptions[0];
                            this.fonction = option?.dataset.fonction || '';
                        }

                        this.refreshMissionOptions();
                    },

                    onMissionChange(event) {
                        this.mission_id = event.target.value;

                        const mission = this.missions.find(
                            item => String(item.id) === String(this.mission_id)
                        );
                        if (!mission) {
                            return;
                        }

                        this.client = mission.client || '';
                        this.date_debut = mission.date_debut || '';
                        this.date_fin = mission.date_fin || '';
                    },
                };
            }
        </script>
    @endpush

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
         x-data="attestationForm(window.attestationConfig)"
         x-init="$nextTick(() => refreshMissionOptions())">
        <div class="p-6">
            <form method="POST" action="{{ route('attestations.update', $attestation) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Consultant -->
                    <div>
                        <label for="consultant_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Consultant *
                        </label>
                        <select name="consultant_id" id="consultant_id"
                                x-model="consultant_id"
                                @change="onConsultantChange($event)"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('consultant_id') border-red-500 @enderror">
                            <option value="">Sélectionner un consultant...</option>
                            @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}" data-fonction="{{ $consultant->fonction }}" {{ old('consultant_id', $attestation->consultant_id) == $consultant->id ? 'selected' : '' }}>
                                    {{ $consultant->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('consultant_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mission -->
                    <div>
                        <label for="mission_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Mission *
                        </label>
                        <select name="mission_id" id="mission_id" x-ref="missionSelect"
                                x-model="mission_id"
                                @change="onMissionChange($event)"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('mission_id') border-red-500 @enderror">
                            <option value="">Sélectionner une mission...</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Les missions proposées dépendent du consultant sélectionné.
                        </p>
                        @error('mission_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fonction -->
                    <div>
                        <label for="fonction" class="block text-sm font-medium text-gray-700 mb-2">
                            Fonction *
                        </label>
                        <input type="text" name="fonction" id="fonction" maxlength="100"
                               x-model="fonction"
                               value="{{ old('fonction', $attestation->fonction) }}"
                               placeholder="Ex : Consultant Senior"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('fonction') border-red-500 @enderror border-gray-300">
                        @error('fonction')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Client (pré-rempli par la mission) -->
                    <div>
                        <label for="client" class="block text-sm font-medium text-gray-700 mb-2">
                            Client *
                        </label>
                        <input type="text" name="client" id="client" maxlength="100"
                               x-model="client"
                               value="{{ old('client', $attestation->client) }}"
                               placeholder="Nom du client"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('client') border-red-500 @enderror border-gray-300">
                        @error('client')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date attestation -->
                    <div>
                        <label for="date_attestation" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de l'attestation *
                        </label>
                        <input type="date" name="date_attestation" id="date_attestation"
                               value="{{ old('date_attestation', $attestation->date_attestation?->format('Y-m-d')) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_attestation') border-red-500 @enderror border-gray-300">
                        @error('date_attestation')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date signature -->
                    <div>
                        <label for="date_signature" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de signature *
                        </label>
                        <input type="date" name="date_signature" id="date_signature"
                               value="{{ old('date_signature', $attestation->date_signature?->format('Y-m-d')) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_signature') border-red-500 @enderror border-gray-300">
                        @error('date_signature')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date début (pré-remplie par la mission) -->
                    <div>
                        <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de début *
                        </label>
                        <input type="date" name="date_debut" id="date_debut"
                               x-model="date_debut"
                               value="{{ old('date_debut', $attestation->date_debut?->format('Y-m-d')) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_debut') border-red-500 @enderror border-gray-300">
                        @error('date_debut')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date fin (optionnelle, pré-remplie par la mission) -->
                    <div>
                        <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de fin <span class="text-gray-400 font-normal">(optionnelle)</span>
                        </label>
                        <input type="date" name="date_fin" id="date_fin"
                               x-model="date_fin"
                               value="{{ old('date_fin', $attestation->date_fin?->format('Y-m-d')) }}"
                               class="w-full rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 @error('date_fin') border-red-500 @enderror border-gray-300">
                        @error('date_fin')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('attestations.index') }}"
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

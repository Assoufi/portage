<?php

// app/Http/Requests/AttestationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consultant_id' => ['required', 'integer', 'exists:consultants,id'],
            'mission_id' => ['required', 'integer', 'exists:missions,id'],
            'fonction' => ['required', 'string', 'max:100'],
            'date_attestation' => ['required', 'date'],
            'date_signature' => ['required', 'date'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'client' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'consultant_id.required' => 'Le consultant est obligatoire.',
            'consultant_id.exists' => 'Le consultant sélectionné n\'existe pas.',

            'mission_id.required' => 'La mission est obligatoire.',
            'mission_id.exists' => 'La mission sélectionnée n\'existe pas.',

            'fonction.required' => 'La fonction est obligatoire.',
            'fonction.max' => 'La fonction ne doit pas dépasser 100 caractères.',

            'date_attestation.required' => 'La date de l\'attestation est obligatoire.',
            'date_attestation.date' => 'La date de l\'attestation doit être une date valide.',

            'date_signature.required' => 'La date de signature est obligatoire.',
            'date_signature.date' => 'La date de signature doit être une date valide.',

            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',

            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',

            'client.required' => 'Le client est obligatoire.',
            'client.max' => 'Le nom du client ne doit pas dépasser 100 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fonction' => $this->fonction ? trim($this->fonction) : null,
            'client' => $this->client ? trim($this->client) : null,
        ]);
    }
}

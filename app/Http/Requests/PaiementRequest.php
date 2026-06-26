<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paiement = $this->route('paiement');

        return [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where('statut', true),
            ],
            'fournisseur_id' => [
                'required',
                'exists:fournisseurs,id',
                Rule::exists('fournisseurs', 'id')->where('statut', true),
            ],
            'mission_id' => [
                'nullable',
                'exists:missions,id',
            ],
            'montant' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'montant_recu' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
                function ($attribute, $value, $fail) {
                    if ($value !== null && (float) $value > (float) $this->montant) {
                        $fail('Le montant reçu ne peut pas dépasser le montant total.');
                    }
                },
            ],
            'date_paiement' => [
                'required',
                'date',
            ],
            'mode_paiement' => [
                'required',
                Rule::in(['virement', 'cheque', 'especes', 'carte']),
            ],
            'remarques' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'statut' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas ou est inactif.',
            'fournisseur_id.required' => 'Le fournisseur est obligatoire.',
            'fournisseur_id.exists' => 'Le fournisseur sélectionné n\'existe pas ou est inactif.',
            'mission_id.exists' => 'La mission sélectionnée n\'existe pas.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'montant.max' => 'Le montant ne peut pas dépasser 99 999 999.99.',
            'montant_recu.numeric' => 'Le montant reçu doit être un nombre.',
            'montant_recu.min' => 'Le montant reçu doit être supérieur ou égal à 0.',
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'date_paiement.date' => 'La date de paiement doit être une date valide.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in' => 'Le mode de paiement sélectionné n\'est pas valide.',
            'remarques.max' => 'Les remarques ne doivent pas dépasser 5000 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'montant' => floatval($this->montant),
            'montant_recu' => $this->montant_recu ? floatval($this->montant_recu) : null,
        ]);
    }
}

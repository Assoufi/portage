<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FactureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $facture = $this->route('facture');

        return [
            'fournisseur_id' => [
                'required',
                'exists:fournisseurs,id',
                Rule::exists('fournisseurs', 'id')->where('statut', true),
            ],
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where('statut', true),
            ],
            'numero_facture' => [
                'required',
                'string',
                'max:50',
                Rule::unique('factures', 'numero_facture')->ignore($facture?->id),
            ],
            'numero_bcm' => [
                'nullable',
                'string',
                'max:50',
            ],
            'date_facture' => [
                'required',
                'date',
            ],
            'designation' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'quantite' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'prix_unitaire' => [
                'nullable',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'total_ht' => [
                'nullable',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'tva' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'montant' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'date_echeance' => [
                'nullable',
                'date',
                'after_or_equal:date_facture',
            ],
            'date_reception' => [
                'nullable',
                'date',
            ],
            'date_paiement' => [
                'nullable',
                'date',
            ],
            'mode_paiement' => [
                'nullable',
                'string',
                'max:50',
            ],
            'reference_paiement' => [
                'nullable',
                'string',
                'max:100',
            ],
            'date_reglement' => [
                'nullable',
                'date',
            ],
            'mode_reglement' => [
                'nullable',
                'string',
                'max:50',
            ],
            'reference_reglement' => [
                'nullable',
                'string',
                'max:100',
            ],
            'beneficiaire' => [
                'nullable',
                'string',
                'max:255',
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
            'fournisseur_id.required'   => 'Le fournisseur est obligatoire.',
            'fournisseur_id.exists'     => 'Le fournisseur sélectionné n\'existe pas ou est inactif.',
            'client_id.required'        => 'Le client est obligatoire.',
            'client_id.exists'          => 'Le client sélectionné n\'existe pas ou est inactif.',
            'numero_facture.required'   => 'Le numéro de facture est obligatoire.',
            'numero_facture.unique'     => 'Ce numéro de facture est déjà utilisé.',
            'numero_facture.max'        => 'Le numéro de facture ne doit pas dépasser 50 caractères.',
            'date_facture.required'     => 'La date de facture est obligatoire.',
            'date_facture.date'         => 'La date de facture doit être une date valide.',
            'montant.required'          => 'Le montant est obligatoire.',
            'montant.numeric'           => 'Le montant doit être un nombre.',
            'montant.min'               => 'Le montant doit être supérieur à 0.',
            'montant.max'               => 'Le montant ne peut pas dépasser 99 999 999.99.',
            'date_echeance.after_or_equal' => 'La date d\'échéance doit être postérieure ou égale à la date de facture.',
            'remarques.max'             => 'Les remarques ne doivent pas dépasser 5000 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'montant'       => floatval($this->montant),
            'total_ht'      => $this->total_ht ? floatval($this->total_ht) : null,
            'prix_unitaire' => $this->prix_unitaire ? floatval($this->prix_unitaire) : null,
            'tva'           => $this->tva ? floatval($this->tva) : null,
            'quantite'      => $this->quantite ? intval($this->quantite) : null,
        ]);
    }
}

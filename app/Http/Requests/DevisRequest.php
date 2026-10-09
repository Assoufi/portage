<?php

// app/Http/Requests/DevisRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DevisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $devis = $this->route('devis');

        return [
            'fournisseur_id' => [
                'required',
                'integer',
                Rule::exists('fournisseurs', 'id')->where('statut', true),
            ],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('statut', true),
            ],
            'mission_id' => [
                'nullable',
                'integer',
                'exists:missions,id',
            ],
            'numero_devis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('devis', 'numero_devis')->ignore($devis?->id),
            ],
            'date_devis' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'quantite' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'prix_unitaire' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fournisseur_id.required' => 'Le fournisseur est obligatoire.',
            'fournisseur_id.exists' => 'Le fournisseur sélectionné n\'existe pas ou est inactif.',

            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas ou est inactif.',

            'mission_id.exists' => 'La mission sélectionnée n\'existe pas.',

            'numero_devis.required' => 'Le numéro de devis est obligatoire.',
            'numero_devis.unique' => 'Ce numéro de devis est déjà utilisé.',
            'numero_devis.max' => 'Le numéro de devis ne doit pas dépasser 50 caractères.',

            'date_devis.required' => 'La date du devis est obligatoire.',
            'date_devis.date' => 'La date du devis doit être une date valide.',

            'description.max' => 'La description ne doit pas dépasser 5000 caractères.',

            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité doit être supérieure à 0.',

            'prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
            'prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
            'prix_unitaire.min' => 'Le prix unitaire doit être positif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantite' => $this->quantite !== null && $this->quantite !== '' ? $this->quantite : null,
            'prix_unitaire' => $this->prix_unitaire !== null && $this->prix_unitaire !== '' ? $this->prix_unitaire : null,
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}

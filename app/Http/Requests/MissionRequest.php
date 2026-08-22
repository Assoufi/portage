<?php
// app/Http/Requests/MissionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Consultant;
use App\Models\Client;
use App\Models\Fournisseur;

class MissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consultant_id' => [
                'required',
                'exists:consultants,id',
                Rule::exists('consultants', 'id')->where('statut', true)
            ],
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where('statut', true)
            ],
            'fournisseur_id' => [
                'required',
                'exists:fournisseurs,id',
                Rule::exists('fournisseurs', 'id')->where('statut', true)
            ],
            'titre' => [
                'nullable',
                'string',
                'max:255'
            ],
            'taux' => [                
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'tjm' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'prix_vente' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'date_debut' => [
                'required',
                'date',                
            ],
            'date_fin' => [
                'nullable',
                'date',
                'after_or_equal:date_debut'
            ],
            'delai_paiement' => [                
                'nullable',
                'integer',
                'min:0',
                'max:365'
            ],
            'formule' => [
                'nullable',
                'string',
                'max:255'
            ],
            'remarques' => [
                'nullable',
                'string',
                'max:5000'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'consultant_id.required' => 'Le consultant est obligatoire.',
            'consultant_id.exists' => 'Le consultant sélectionné n\'existe pas ou est inactif.',
            
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas ou est inactif.',
            
            'fournisseur_id.required' => 'Le fournisseur est obligatoire.',
            'fournisseur_id.exists' => 'Le fournisseur sélectionné n\'existe pas ou est inactif.',
            
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            
            'taux.numeric' => 'Le taux doit être un nombre.',
            'taux.min' => 'Le taux doit être supérieur ou égal à 0.',
            
            'tjm.required' => 'Le TJM est obligatoire.',
            'tjm.numeric' => 'Le TJM doit être un nombre.',
            'tjm.min' => 'Le TJM doit être supérieur ou égal à 0.',
            
            'prix_vente.required' => 'Le prix de vente est obligatoire.',
            'prix_vente.numeric' => 'Le prix de vente doit être un nombre.',
            'prix_vente.min' => 'Le prix de vente doit être supérieur ou égal à 0.',
            
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_debut.after_or_equal' => 'La date de début doit être aujourd\'hui ou une date future.',
            
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            
            'delai_paiement.integer' => 'Le délai de paiement doit être un nombre entier.',
            'delai_paiement.min' => 'Le délai de paiement doit être supérieur ou égal à 0.',
            'delai_paiement.max' => 'Le délai de paiement ne peut pas dépasser 365 jours.',
            
            'remarques.max' => 'Les remarques ne doivent pas dépasser 5000 caractères.'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier la cohérence des dates
            if ($this->date_debut && $this->date_fin) {
                if (strtotime($this->date_fin) < strtotime($this->date_debut)) {
                    $validator->errors()->add('date_fin', 'La date de fin doit être postérieure ou égale à la date de début.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'taux' => floatval($this->taux),
            'tjm' => floatval($this->tjm),
            'prix_vente' => floatval($this->prix_vente),
            'delai_paiement' => intval($this->delai_paiement)
        ]);
    }

    private function getClientDevise(): string
    {
        if ($this->client_id) {
            $client = Client::find($this->client_id);
            return $client ? $client->devise : 'MAD';
        }
        return 'MAD';
    }
}
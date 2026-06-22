<?php
// app/Http/Requests/ConsultantRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ModePaiement;

class ConsultantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Tous les utilisateurs authentifiés peuvent effectuer ces actions
    }

    public function rules(): array
    {
        $consultantId = $this->route('consultant') ? $this->route('consultant')->id : null;

        return [
            'nom' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:consultants,email,' . $consultantId,
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'tel' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,4}$/'
            ],
            'rib' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]{10,50}$/'
            ],
            'mode_paiement' => [
                'required',
                'string',
                'in:' . implode(',', ModePaiement::values())
            ],
            'statut' => [
                'required',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du consultant est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'nom.regex' => 'Le nom ne doit contenir que des lettres, espaces, tirets et apostrophes.',
            
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'email.regex' => 'Veuillez saisir une adresse email valide.',
            
            'tel.required' => 'Le numéro de téléphone est obligatoire.',
            'tel.regex' => 'Veuillez saisir un numéro de téléphone valide.',
            
            'rib.regex' => 'Le RIB ne doit contenir que des lettres majuscules et des chiffres.',
            
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in' => 'Le mode de paiement sélectionné n\'est pas valide.',
            
            'statut.required' => 'Le statut est obligatoire.',
            'statut.boolean' => 'Le statut doit être vrai ou faux.'
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'email' => 'adresse email',
            'tel' => 'téléphone',
            'rib' => 'RIB',
            'mode_paiement' => 'mode de paiement',
            'statut' => 'statut'
        ];
    }

    protected function prepareForValidation(): void
    {
        // Nettoyer les données avant validation
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'nom' => ucwords(strtolower(trim($this->nom))),
            'tel' => preg_replace('/\s+/', '', $this->tel)
        ]);
    }
}
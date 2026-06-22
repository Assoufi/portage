<?php
// app/Http/Requests/ClientRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client') ? $this->route('client')->id : null;

        return [
            'nom' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'
            ],
            'adresse' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:clients,email,' . $clientId,
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'ice' => [
                'required',
                'string',
                'size:15',
                'unique:clients,ice,' . $clientId,
                'regex:/^[A-Z0-9]{15}$/',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^[A-Z0-9]{15}$/', $value)) {
                        $fail('L\'ICE doit être composé exactement de 15 caractères alphanumériques majuscules.');
                    }
                }
            ],
            'tva' => [
                'required',
                'numeric',
                'between:0,100',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'devise' => [
                'required',
                'string',
                'size:3',
                Rule::in(['MAD', 'EUR', 'USD', 'GBP', 'CAD'])
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
            'nom.required' => 'Le nom du client est obligatoire.',  
            'nom.max' => 'Le nom ne doit pas dépasser 100 caractères.',  
            'nom.regex' => 'Le nom ne doit contenir que des lettres, espaces, tirets et apostrophes.',  
            
            'adresse.max' => 'L\'adresse ne doit pas dépasser 1000 caractères.',
            
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            
            'ice.required' => 'L\'ICE est obligatoire.',
            'ice.size' => 'L\'ICE doit contenir exactement 15 caractères.',
            'ice.unique' => 'Cet ICE est déjà utilisé.',
            'ice.regex' => 'L\'ICE doit contenir uniquement des lettres majuscules et des chiffres.',
            
            'tva.required' => 'Le taux de TVA est obligatoire.',
            'tva.numeric' => 'Le taux de TVA doit être un nombre.',
            'tva.between' => 'Le taux de TVA doit être compris entre 0 et 100.',
            
            'devise.required' => 'La devise est obligatoire.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.in' => 'La devise sélectionnée n\'est pas supportée.',
            
            'statut.required' => 'Le statut est obligatoire.',
            'statut.boolean' => 'Le statut doit être vrai ou faux.'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => ucwords(strtolower(trim($this->nom))),  
            'email' => strtolower(trim($this->email)),
            'ice' => strtoupper(preg_replace('/\s+/', '', $this->ice)),
            'tva' => floatval($this->tva)
        ]);
    }
}
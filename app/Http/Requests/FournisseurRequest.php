<?php
// app/Http/Requests/FournisseurRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fournisseurId = $this->route('fournisseur') ? $this->route('fournisseur')->id : null;

        return [
            'nom' => [
                'required',
                'string',
                'max:255',
                'unique:fournisseurs,nom,' . $fournisseurId,
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
                'unique:fournisseurs,email,' . $fournisseurId,
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'ice' => [
                'nullable',
                'string',
                'size:15',
                'unique:fournisseurs,ice,' . $fournisseurId,
                'regex:/^[A-Z0-9]{15}$/',
                function ($attribute, $value, $fail) {
                    if ($value && !preg_match('/^[A-Z0-9]{15}$/', $value)) {
                        $fail('L\'ICE doit être composé exactement de 15 caractères alphanumériques majuscules.');
                    }
                }
            ],
            'taux' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'ville' => [
                'nullable',
                'string',
                'max:255'
            ],
            'nom_responsable' => [
                'nullable',
                'string',
                'max:255'
            ],
            'rib' => [
                'nullable',
                'string',
                'max:255'
            ],
            'statut' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du fournisseur est obligatoire.',
            'nom.unique' => 'Ce nom de fournisseur est déjà utilisé.',
            'nom.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            
            'adresse.max' => 'L\'adresse ne doit pas dépasser 1000 caractères.',
            
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            
            'ice.size' => 'L\'ICE doit contenir exactement 15 caractères.',
            'ice.unique' => 'Cet ICE est déjà utilisé.',
            'ice.regex' => 'L\'ICE doit contenir uniquement des lettres majuscules et des chiffres.',
            
            'taux.numeric' => 'Le taux doit être un nombre.',
            'taux.min' => 'Le taux doit être supérieur ou égal à 0.',
            'taux.max' => 'Le taux doit être inférieur ou égal à 100.',
            
            'ville.max' => 'La ville ne doit pas dépasser 255 caractères.',
            'nom_responsable.max' => 'Le nom du responsable ne doit pas dépasser 255 caractères.',
            'rib.max' => 'Le RIB ne doit pas dépasser 255 caractères.',
            
            'statut.boolean' => 'Le statut doit être vrai ou faux.'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'ice' => strtoupper(preg_replace('/\s+/', '', $this->ice)),
            'ville' => trim($this->ville),
            'nom_responsable' => trim($this->nom_responsable),
            'rib' => trim($this->rib),
            'taux' => floatval($this->taux)
        ]);
    }
}
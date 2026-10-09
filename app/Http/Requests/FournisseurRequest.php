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
                'unique:fournisseurs,nom,'.$fournisseurId,
            ],
            'adresse' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'ice' => [
                'nullable',
                'string',
                'size:15',
                'unique:fournisseurs,ice,'.$fournisseurId,
                'regex:/^[A-Z0-9]{15}$/',
                function ($attribute, $value, $fail) {
                    if ($value && ! preg_match('/^[A-Z0-9]{15}$/', $value)) {
                        $fail('L\'ICE doit être composé exactement de 15 caractères alphanumériques majuscules.');
                    }
                },
            ],
            'taux' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'ville' => [
                'nullable',
                'string',
                'max:255',
            ],
            'responsable' => [
                'nullable',
                'string',
                'max:255',
            ],
            'rib' => [
                'nullable',
                'string',
                'max:255',
            ],
            'iban' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9]+$/',
            ],
            'signature' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
            'footer' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'statut' => [
                'nullable',
                'boolean',
            ],
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

            'ice.size' => 'L\'ICE doit contenir exactement 15 caractères.',
            'ice.unique' => 'Cet ICE est déjà utilisé.',
            'ice.regex' => 'L\'ICE doit contenir uniquement des lettres majuscules et des chiffres.',

            'taux.numeric' => 'Le taux doit être un nombre.',
            'taux.min' => 'Le taux doit être supérieur ou égal à 0.',
            'taux.max' => 'Le taux doit être inférieur ou égal à 100.',

            'ville.max' => 'La ville ne doit pas dépasser 255 caractères.',
            'responsable.max' => 'Le nom du responsable ne doit pas dépasser 255 caractères.',
            'rib.max' => 'Le RIB ne doit pas dépasser 255 caractères.',

            'iban.max' => 'L\'IBAN ne doit pas dépasser 20 caractères.',
            'iban.regex' => 'L\'IBAN ne doit contenir que des lettres et des chiffres.',

            'signature.image' => 'La signature doit être une image.',
            'signature.mimes' => 'La signature doit être au format PNG, JPG, JPEG ou WEBP.',
            'signature.max' => 'La signature ne doit pas dépasser 2 Mo.',

            'logo.image' => 'Le logo doit être une image.',
            'logo.mimes' => 'Le logo doit être au format PNG, JPG, JPEG ou WEBP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',

            'footer.max' => 'Le footer ne doit pas dépasser 1000 caractères.',

            'statut.boolean' => 'Le statut doit être vrai ou faux.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'ice' => strtoupper(preg_replace('/\s+/', '', $this->ice)),
            'ville' => trim($this->ville),
            'responsable' => trim($this->responsable),
            'rib' => trim($this->rib),
            'iban' => $this->iban ? strtoupper(preg_replace('/\s+/', '', $this->iban)) : null,
            'taux' => floatval($this->taux),
        ]);
    }
}

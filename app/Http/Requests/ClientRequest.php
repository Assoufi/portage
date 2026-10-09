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
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/',
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
            'type_identification' => [
                'nullable',
                'string',
                'max:50',
            ],
            'num_identification' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients', 'num_identification')
                    ->ignore($clientId)
                    ->where(fn ($query) => $query->whereNotNull('num_identification')),
            ],
            'tva' => [
                'required',
                'numeric',
                'between:0,100',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'devise' => [
                'required',
                'string',
                'size:3',
                Rule::in(['MAD', 'EUR', 'USD', 'GBP', 'CAD']),
            ],
            'statut' => [
                'required',
                'boolean',
            ],
            'adresse_facturation' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'delai_paiement' => [
                'nullable',
                'integer',
                'min:0',
                'max:365',
            ],
            'remarques' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'periodicite' => [
                'nullable',
                Rule::in(['Mensuelle', 'Occasionnelle']),
            ],
            'telephone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'mode_livraison' => [
                'nullable',
                'array',
            ],
            'mode_livraison.*' => [
                Rule::in(['Papier', 'Email', 'Whatsapp']),
            ],
            'notifyto' => [
                'nullable',
                'string',
                'max:250',
                $this->regleListeEmails(),
            ],
            'notifycc' => [
                'nullable',
                'string',
                'max:250',
                $this->regleListeEmails(),
            ],
        ];
    }

    /**
     * Valide une liste d'adresses email séparées par des points-virgules.
     */
    private function regleListeEmails(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            foreach (explode(';', (string) $value) as $email) {
                $email = trim($email);

                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail('Le champ :attribute contient une adresse email invalide : '.$email.'.');

                    return;
                }
            }
        };
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du client est obligatoire.',
            'nom.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'nom.regex' => 'Le nom ne doit contenir que des lettres, espaces, tirets et apostrophes.',

            'adresse.max' => 'L\'adresse ne doit pas dépasser 1000 caractères.',

            'email.email' => 'Veuillez saisir une adresse email valide.',

            'type_identification.max' => 'Le type d\'identification ne doit pas dépasser 50 caractères.',

            'num_identification.unique' => 'Ce numéro d\'identification est déjà utilisé.',
            'num_identification.max' => 'Le numéro d\'identification ne doit pas dépasser 50 caractères.',

            'tva.required' => 'Le taux de TVA est obligatoire.',
            'tva.numeric' => 'Le taux de TVA doit être un nombre.',
            'tva.between' => 'Le taux de TVA doit être compris entre 0 et 100.',

            'devise.required' => 'La devise est obligatoire.',
            'devise.size' => 'La devise doit contenir exactement 3 caractères.',
            'devise.in' => 'La devise sélectionnée n\'est pas supportée.',

            'statut.required' => 'Le statut est obligatoire.',
            'statut.boolean' => 'Le statut doit être vrai ou faux.',

            'adresse_facturation.max' => 'L\'adresse de facturation ne doit pas dépasser 1000 caractères.',

            'delai_paiement.integer' => 'Le délai de paiement doit être un nombre entier de jours.',
            'delai_paiement.min' => 'Le délai de paiement ne peut pas être négatif.',
            'delai_paiement.max' => 'Le délai de paiement ne peut pas dépasser 365 jours.',

            'remarques.max' => 'Les remarques ne doivent pas dépasser 2000 caractères.',

            'periodicite.in' => 'La périodicité doit être « Mensuelle » ou « Occasionnelle ».',

            'telephone.max' => 'Le téléphone ne doit pas dépasser 20 caractères.',

            'mode_livraison.array' => 'Le mode de livraison est invalide.',
            'mode_livraison.*.in' => 'Le mode de livraison doit être « Papier », « Email » ou « Whatsapp ».',

            'notifyto.max' => 'Le champ « Notifier à » ne doit pas dépasser 250 caractères.',
            'notifycc.max' => 'Le champ « Copie notification » ne doit pas dépasser 250 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $modeLivraison = $this->mode_livraison;

        if (! is_array($modeLivraison)) {
            $modeLivraison = $modeLivraison ? [$modeLivraison] : [];
        }

        $modeLivraison = array_values(array_filter($modeLivraison, fn ($valeur) => $valeur !== null && $valeur !== ''));

        $this->merge([
            'nom' => ucwords(strtolower(trim($this->nom))),
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'num_identification' => $this->num_identification ? strtoupper(trim($this->num_identification)) : null,
            'tva' => floatval($this->tva),
            'periodicite' => $this->periodicite ? trim($this->periodicite) : null,
            'mode_livraison' => $modeLivraison === [] ? null : $modeLivraison,
            'telephone' => $this->telephone ? trim($this->telephone) : null,
            'notifyto' => $this->notifyto ? trim($this->notifyto) : null,
            'notifycc' => $this->notifycc ? trim($this->notifycc) : null,
        ]);
    }
}

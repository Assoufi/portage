<?php

// app/Http/Requests/CommandeRequest.php

namespace App\Http\Requests;

use App\Models\Commande;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $commande = $this->route('commande');

        return [
            'mission_id' => [
                'required',
                'integer',
                Rule::exists('missions', 'id')->whereNull('deleted_at'),
            ],
            'num_commande' => [
                'required',
                'string',
                'max:50',
                Rule::unique('commandes', 'num_commande')->ignore($commande?->id),
            ],
            'date_commande' => [
                'required',
                'date',
            ],
            'date_debut' => [
                'nullable',
                'date',
            ],
            'date_fin' => [
                'nullable',
                'date',
                'after_or_equal:date_debut',
            ],
            'quantite' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'tjm' => [
                'required',
                'numeric',
                'min:0',
            ],
            'statut' => [
                'required',
                Rule::in(array_keys(Commande::STATUTS)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mission_id.required' => 'La mission est obligatoire.',
            'mission_id.exists' => 'La mission sélectionnée n\'existe pas.',

            'num_commande.required' => 'Le numéro de commande est obligatoire.',
            'num_commande.unique' => 'Ce numéro de commande est déjà utilisé.',
            'num_commande.max' => 'Le numéro de commande ne doit pas dépasser 50 caractères.',

            'date_commande.required' => 'La date de commande est obligatoire.',
            'date_commande.date' => 'La date de commande doit être une date valide.',

            'date_debut.date' => 'La date de début doit être une date valide.',

            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',

            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité doit être supérieure à 0.',

            'tjm.required' => 'Le TJM est obligatoire.',
            'tjm.numeric' => 'Le TJM doit être un nombre.',
            'tjm.min' => 'Le TJM doit être positif.',

            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantite' => $this->quantite !== null && $this->quantite !== '' ? $this->quantite : null,
            'tjm' => $this->tjm !== null && $this->tjm !== '' ? $this->tjm : null,
            'num_commande' => $this->num_commande ? trim($this->num_commande) : null,
        ]);
    }
}

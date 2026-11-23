<?php

namespace App\Http\Requests;

use App\Models\Facture;
use App\Services\AvoirService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvoirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $avoir = $this->route('avoir');

        return [
            'facture_id' => [
                'required',
                'exists:factures,id',
            ],
            'numero_avoir' => [
                'required',
                'string',
                'max:50',
                Rule::unique('avoirs', 'numero_avoir')->ignore($avoir?->id),
            ],
            'date_avoir' => [
                'required',
                'date',
            ],
            'type' => [
                'required',
                Rule::in(['total', 'partiel']),
            ],
            'motif' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'taux_tva' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'remarques' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'statut' => [
                'boolean',
            ],
            'details' => [
                'required',
                'array',
                'min:1',
            ],
            'details.*.designation' => [
                'required',
                'string',
                'max:1000',
            ],
            'details.*.quantite' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'details.*.prix_unitaire' => [
                'required',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'facture_id.required' => 'La facture à annuler est obligatoire.',
            'facture_id.exists' => 'La facture sélectionnée n\'existe pas.',
            'numero_avoir.required' => 'Le numéro d\'avoir est obligatoire.',
            'numero_avoir.unique' => 'Ce numéro d\'avoir est déjà utilisé.',
            'date_avoir.required' => 'La date de l\'avoir est obligatoire.',
            'date_avoir.date' => 'La date de l\'avoir doit être une date valide.',
            'type.required' => 'Le type d\'annulation est obligatoire.',
            'type.in' => 'Le type d\'annulation doit être total ou partiel.',
            'taux_tva.required' => 'Le taux de TVA est obligatoire.',
            'details.required' => 'Au moins une ligne d\'avoir est obligatoire.',
            'details.min' => 'Au moins une ligne d\'avoir est obligatoire.',
            'details.*.designation.required' => 'La désignation de chaque ligne est obligatoire.',
            'details.*.quantite.required' => 'La quantité de chaque ligne est obligatoire.',
            'details.*.quantite.min' => 'La quantité doit être supérieure à 0.',
            'details.*.prix_unitaire.required' => 'Le prix unitaire de chaque ligne est obligatoire.',
            'details.*.prix_unitaire.min' => 'Le prix unitaire doit être positif.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $facture = Facture::find($this->input('facture_id'));

            if (! $facture) {
                return;
            }

            $taux = (float) ($this->input('taux_tva') ?? $facture->taux_tva ?? 20);

            $total = collect($this->input('details', []))->sum(
                fn ($detail) => (float) ($detail['quantite'] ?? 0)
                    * (float) ($detail['prix_unitaire'] ?? 0)
                    * (1 + $taux / 100)
            );

            $avoir = $this->route('avoir');
            $restant = app(AvoirService::class)->montantRestant($facture, $avoir?->id);

            if ($total > $restant + 0.01) {
                $validator->errors()->add(
                    'details',
                    'Le montant de l\'avoir ('.number_format($total, 2, ',', ' ').' ) dépasse le montant restant de la facture ('.number_format($restant, 2, ',', ' ').' ).'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'taux_tva' => $this->taux_tva !== null && $this->taux_tva !== '' ? floatval($this->taux_tva) : 20,
        ]);
    }
}

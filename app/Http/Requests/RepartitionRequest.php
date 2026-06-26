<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RepartitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paiement_id' => [
                'required',
                'exists:paiements,id',
            ],
            'consultant_id' => [
                'required',
                'exists:consultants,id',
                Rule::exists('consultants', 'id')->where('statut', true),
            ],
            'montant' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
                function ($attribute, $value, $fail) {
                    $paiementId = $this->paiement_id ?: $this->route('paiement')?->id;
                    if ($paiementId) {
                        $paiement = \App\Models\Paiement::find($paiementId);
                        if ($paiement) {
                            $dejaReparti = $paiement->repartitions()->sum('montant');
                            $repartitionId = $this->route('repartition')?->id;
                            if ($repartitionId) {
                                $dejaReparti -= \App\Models\Repartition::find($repartitionId)?->montant ?? 0;
                            }
                            if (($dejaReparti + (float) $value) > $paiement->montant) {
                                $fail("Le montant total des répartitions ({$dejaReparti}) + ce montant ($value) dépasse le montant du paiement ({$paiement->montant}).");
                            }
                        }
                    }
                },
            ],
            'date_paiement' => [
                'required',
                'date',
            ],
            'remarques' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'rib' => [
                'nullable',
                'string',
                'max:50',
            ],
            'banque' => [
                'nullable',
                'string',
                'max:100',
            ],
            'mode_paiement' => [
                'required',
                Rule::in(['virement', 'cheque', 'especes']),
            ],
            'telephone' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'paiement_id.required' => 'Le paiement est obligatoire.',
            'paiement_id.exists' => 'Le paiement sélectionné n\'existe pas.',
            'consultant_id.required' => 'Le consultant est obligatoire.',
            'consultant_id.exists' => 'Le consultant sélectionné n\'existe pas ou est inactif.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'montant.max' => 'Le montant ne peut pas dépasser 99 999 999.99.',
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'date_paiement.date' => 'La date de paiement doit être une date valide.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in' => 'Le mode de paiement sélectionné n\'est pas valide.',
            'remarques.max' => 'Les remarques ne doivent pas dépasser 5000 caractères.',
            'rib.max' => 'Le RIB ne doit pas dépasser 50 caractères.',
            'banque.max' => 'Le nom de la banque ne doit pas dépasser 100 caractères.',
            'telephone.max' => 'Le téléphone ne doit pas dépasser 20 caractères.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($paiement = $this->route('paiement')) {
            $this->merge([
                'paiement_id' => $paiement->id,
            ]);
        }

        $this->merge([
            'montant' => floatval($this->montant),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Repartition;
use Illuminate\Support\Facades\DB;

class RepartitionService
{
    public function createRepartition(Paiement $paiement, array $data): Repartition
    {
        return DB::transaction(function () use ($paiement, $data) {
            $data['paiement_id'] = $paiement->id;

            $dejaReparti = $paiement->repartitions()->sum('montant');
            $nouveauTotal = $dejaReparti + (float) $data['montant'];

            if ($nouveauTotal > $paiement->montant) {
                throw new \RuntimeException(
                    "Le montant total des répartitions ($dejaReparti) + ce montant ({$data['montant']}) " .
                    "dépasse le montant du paiement ({$paiement->montant})."
                );
            }

            return Repartition::create($data);
        });
    }

    public function updateRepartition(Repartition $repartition, array $data): Repartition
    {
        return DB::transaction(function () use ($repartition, $data) {
            $paiement = $repartition->paiement;

            $dejaReparti = $paiement->repartitions()
                ->where('id', '!=', $repartition->id)
                ->sum('montant');

            $nouveauTotal = $dejaReparti + (float) ($data['montant'] ?? $repartition->montant);

            if ($nouveauTotal > $paiement->montant) {
                throw new \RuntimeException(
                    "Le montant total des répartitions ($dejaReparti) + ce montant ({$data['montant']}) " .
                    "dépasse le montant du paiement ({$paiement->montant})."
                );
            }

            $repartition->update($data);
            return $repartition->fresh();
        });
    }

    public function deleteRepartition(Repartition $repartition): void
    {
        DB::transaction(function () use ($repartition) {
            $repartition->delete();
        });
    }
}

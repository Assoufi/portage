<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Repartition;
use Illuminate\Support\Facades\DB;

class PaiementService
{
    public function createPaiement(array $data): Paiement
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['reference'])) {
                $data['reference'] = Paiement::genererReference();
            }

            return Paiement::create($data);
        });
    }

    public function updatePaiement(Paiement $paiement, array $data): Paiement
    {
        return DB::transaction(function () use ($paiement, $data) {
            $paiement->update($data);
            return $paiement->fresh();
        });
    }

    public function deletePaiement(Paiement $paiement): void
    {
        DB::transaction(function () use ($paiement) {
            if ($paiement->repartitions()->exists()) {
                throw new \RuntimeException('Impossible de supprimer ce paiement car il a des répartitions associées.');
            }

            $paiement->delete();
        });
    }

    public function getSoldeRestant(Paiement $paiement): float
    {
        $totalReparti = $paiement->repartitions()->sum('montant');
        return $paiement->montant - $totalReparti;
    }

    public function getStats(): array
    {
        return [
            'total_paiements' => Paiement::count(),
            'montant_total' => Paiement::sum('montant'),
            'montant_recu_total' => Paiement::sum('montant_recu') ?? 0,
            'paiements_actifs' => Paiement::actif()->count(),
            'repartitions_total' => Repartition::count(),
            'montant_reparti_total' => Repartition::sum('montant'),
        ];
    }
}

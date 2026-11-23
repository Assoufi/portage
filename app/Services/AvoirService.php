<?php

namespace App\Services;

use App\Models\Avoir;
use App\Models\DetailAvoir;
use App\Models\Facture;
use App\Support\NumeroRetry;
use Illuminate\Support\Facades\DB;

class AvoirService
{
    private function prepareDetail(array $detail, Avoir $avoir): array
    {
        $montantHt = (float) ($detail['quantite'] ?? 1) * (float) ($detail['prix_unitaire'] ?? 0);
        $montantTva = $montantHt * (float) ($avoir->taux_tva ?? 20) / 100;

        $detail['montant_ht'] = $montantHt;
        $detail['montant_tva'] = $montantTva;
        $detail['montant_ttc'] = $montantHt + $montantTva;

        return $detail;
    }

    public function createAvoir(array $data): Avoir
    {
        return DB::transaction(function () use ($data) {
            $data = array_merge(['total_ht' => 0, 'montant_ttc' => 0], $data);

            if (! empty($data['numero_avoir'])) {
                $avoir = Avoir::create($data);
            } else {
                $avoir = NumeroRetry::sauvegarder(
                    fn () => Avoir::genererNumeroAvoir(),
                    fn (string $numero) => Avoir::create(array_merge($data, ['numero_avoir' => $numero]))
                );
            }

            if (! empty($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $detail['avoir_id'] = $avoir->id;
                    DetailAvoir::create($this->prepareDetail($detail, $avoir));
                }

                $avoir->load('details');
                $avoir->calculateTotals();
                $avoir->save();
            }

            return $avoir->fresh();
        });
    }

    public function updateAvoir(Avoir $avoir, array $data): Avoir
    {
        return DB::transaction(function () use ($avoir, $data) {
            $avoir->update($data);

            if (isset($data['details'])) {
                $avoir->details()->delete();

                foreach ($data['details'] as $detail) {
                    $detail['avoir_id'] = $avoir->id;
                    DetailAvoir::create($this->prepareDetail($detail, $avoir->fresh()));
                }

                $avoir->load('details');
                $avoir->calculateTotals();
                $avoir->save();
            }

            return $avoir->fresh();
        });
    }

    public function deleteAvoir(Avoir $avoir): void
    {
        DB::transaction(function () use ($avoir) {
            $avoir->details()->delete();
            $avoir->delete();
        });
    }

    /**
     * Montant déjà annulé par des avoirs sur une facture,
     * en excluant éventuellement l'avoir en cours d'édition.
     */
    public function montantDejaAvoire(Facture $facture, ?int $excludeAvoirId = null): float
    {
        return (float) Avoir::query()
            ->where('facture_id', $facture->id)
            ->when($excludeAvoirId, fn ($q) => $q->whereKeyNot($excludeAvoirId))
            ->sum('montant_ttc');
    }

    public function montantRestant(Facture $facture, ?int $excludeAvoirId = null): float
    {
        return max(0, (float) ($facture->montant_ttc ?? 0) - $this->montantDejaAvoire($facture, $excludeAvoirId));
    }

    public function getStats(): array
    {
        $totalAvoirs = Avoir::count();
        $montantTotal = (float) Avoir::sum('montant_ttc');
        $avoirsMoisCourant = Avoir::whereMonth('date_avoir', now()->month)
            ->whereYear('date_avoir', now()->year)
            ->count();
        $totalFactures = Avoir::distinct()->count('facture_id');

        return compact('totalAvoirs', 'montantTotal', 'avoirsMoisCourant', 'totalFactures');
    }
}

<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\DetailFacture;
use Illuminate\Support\Facades\DB;

class FactureService
{
    public function createFacture(array $data): Facture
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['numero_facture'])) {
                $data['numero_facture'] = Facture::genererNumeroFacture();
            }

            if (!isset($data['montant']) && isset($data['total_ht']) && isset($data['tva'])) {
                $data['montant'] = $data['total_ht'] * (1 + $data['tva'] / 100);
            }

            $facture = Facture::create($data);

            if (!empty($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $detail['facture_id'] = $facture->id;
                    DetailFacture::create($detail);
                }

                $facture->load('details');
            }

            return $facture->fresh();
        });
    }

    public function updateFacture(Facture $facture, array $data): Facture
    {
        return DB::transaction(function () use ($facture, $data) {
            if (!isset($data['montant']) && isset($data['total_ht']) && isset($data['tva'])) {
                $data['montant'] = $data['total_ht'] * (1 + $data['tva'] / 100);
            }

            $facture->update($data);

            if (isset($data['details'])) {
                $facture->details()->delete();

                foreach ($data['details'] as $detail) {
                    $detail['facture_id'] = $facture->id;
                    DetailFacture::create($detail);
                }

                $facture->load('details');
            }

            return $facture->fresh();
        });
    }

    public function deleteFacture(Facture $facture): void
    {
        DB::transaction(function () use ($facture) {
            $facture->details()->delete();
            $facture->delete();
        });
    }

    public function marquerReglee(Facture $facture, array $data): Facture
    {
        return DB::transaction(function () use ($facture, $data) {
            $facture->update([
                'date_reglement'      => $data['date_reglement'] ?? now(),
                'mode_reglement'      => $data['mode_reglement'] ?? null,
                'reference_reglement' => $data['reference_reglement'] ?? null,
            ]);

            return $facture->fresh();
        });
    }

    public function getStats(): array
    {
        $totalFactures     = Facture::count();
        $montantTotal      = Facture::sum('montant');
        $montantTotalHt    = Facture::sum('total_ht');
        $facturesReglees   = Facture::reglee()->count();
        $montantRegle      = Facture::reglee()->sum('montant');
        $facturesEnAttente = Facture::nonReglee()->actif()->count();
        $montantEnAttente  = Facture::nonReglee()->actif()->sum('montant');
        $facturesEnRetard  = Facture::nonReglee()
            ->actif()
            ->where('date_echeance', '<', now())
            ->count();
        $montantEnRetard   = Facture::nonReglee()
            ->actif()
            ->where('date_echeance', '<', now())
            ->sum('montant');

        return compact(
            'totalFactures',
            'montantTotal',
            'montantTotalHt',
            'facturesReglees',
            'montantRegle',
            'facturesEnAttente',
            'montantEnAttente',
            'facturesEnRetard',
            'montantEnRetard'
        );
    }
}

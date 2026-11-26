<?php

namespace App\Services;

use App\Models\DetailFacture;
use App\Models\Facture;
use App\Support\NumeroRetry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FactureService
{
    private function prepareDetail(array $detail, Facture $facture): array
    {
        $montantHt = (float) ($detail['quantite'] ?? 1) * (float) ($detail['prix_unitaire'] ?? 0);
        $montantTva = $montantHt * (float) ($facture->taux_tva ?? 20) / 100;
        $detail['montant_ht'] = $montantHt;
        $detail['montant_tva'] = $montantTva;
        $detail['montant_ttc'] = $montantHt + $montantTva;

        return $detail;
    }

    public function createFacture(array $data): Facture
    {
        return DB::transaction(function () use ($data) {
            // Numéro saisi par l'utilisateur : la collision doit remonter telle quelle
            if (isset($data['numero_facture']) && $data['numero_facture'] !== '') {
                $facture = Facture::create($data);
            } else {
                $fournisseurId = $data['fournisseur_id'] ?? null;
                $facture = NumeroRetry::sauvegarder(
                    fn () => Facture::genererNumeroFacturePourFournisseur($fournisseurId),
                    fn (string $numero) => Facture::create(array_merge($data, ['numero_facture' => $numero]))
                );
            }

            if (! empty($data['details'])) {
                foreach ($data['details'] as $detail) {
                    $detail['facture_id'] = $facture->id;
                    DetailFacture::create($this->prepareDetail($detail, $facture));
                }

                $facture->load('details');
                $facture->calculateTotals();
                $facture->save();
            }

            return $facture->fresh();
        });
    }

    public function updateFacture(Facture $facture, array $data): Facture
    {
        return DB::transaction(function () use ($facture, $data) {
            $facture->update($data);

            if (isset($data['details'])) {
                $facture->details()->delete();

                foreach ($data['details'] as $detail) {
                    $detail['facture_id'] = $facture->id;
                    DetailFacture::create($this->prepareDetail($detail, $facture->fresh(['client'])));
                }

                $facture->load('details');
                $facture->calculateTotals();
                $facture->save();
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
                'date_reglement' => $data['date_reglement'] ?? now(),
                'mode_reglement' => $data['mode_reglement'] ?? null,
                'reference_reglement' => $data['reference_reglement'] ?? null,
            ]);

            return $facture->fresh();
        });
    }

    public function clonerFacture(Facture $facture): Facture
    {
        return DB::transaction(function () use ($facture) {
            $nouvelleFacture = new Facture;
            $donnees = $facture->except(['id', 'created_at', 'updated_at', 'numero_facture']);
            $nouvelleFacture->fill($donnees);

            // Numéro auto-généré : régénère en cas de collision avec une autre création concurrente
            NumeroRetry::sauvegarder(
                fn () => Facture::genererNumeroFacturePourFournisseur($facture->fournisseur_id),
                function (string $numero) use ($nouvelleFacture) {
                    $nouvelleFacture->numero_facture = $numero;
                    $nouvelleFacture->save();
                }
            );

            foreach ($facture->details as $detail) {
                $detailData = $detail->toArray();
                unset($detailData['id'], $detailData['facture_id'], $detailData['created_at'], $detailData['updated_at']);
                $detailData['facture_id'] = $nouvelleFacture->id;
                DetailFacture::create($detailData);
            }

            $nouvelleFacture->load('details');
            $nouvelleFacture->calculateTotals();
            $nouvelleFacture->save();

            return $nouvelleFacture->fresh();
        });
    }

    public function getStats(): array
    {
        // Une seule passe sur la table (au lieu de 9 requêtes count()/sum())
        // + cache court : les stats globales ne changent pas à chaque filtre.
        return Cache::remember('factures.stats', now()->addMinutes(2), function () {
            $maintenant = now()->toDateTimeString();

            $row = Facture::query()->selectRaw(
                'COUNT(*) AS totalFactures,
                 COALESCE(SUM(montant_ttc), 0) AS montantTotal,
                 COALESCE(SUM(total_ht), 0) AS montantTotalHt,
                 COALESCE(SUM(CASE WHEN date_reglement IS NOT NULL THEN 1 ELSE 0 END), 0) AS facturesReglees,
                 COALESCE(SUM(CASE WHEN date_reglement IS NOT NULL THEN montant_ttc ELSE 0 END), 0) AS montantRegle,
                 COALESCE(SUM(CASE WHEN date_reglement IS NULL AND statut = 1 THEN 1 ELSE 0 END), 0) AS facturesEnAttente,
                 COALESCE(SUM(CASE WHEN date_reglement IS NULL AND statut = 1 THEN montant_ttc ELSE 0 END), 0) AS montantEnAttente,
                 COALESCE(SUM(CASE WHEN date_reglement IS NULL AND statut = 1 AND date_echeance < ? THEN 1 ELSE 0 END), 0) AS facturesEnRetard,
                 COALESCE(SUM(CASE WHEN date_reglement IS NULL AND statut = 1 AND date_echeance < ? THEN montant_ttc ELSE 0 END), 0) AS montantEnRetard',
                [$maintenant, $maintenant]
            )->first();

            return [
                'totalFactures' => (int) $row->totalFactures,
                'montantTotal' => (float) $row->montantTotal,
                'montantTotalHt' => (float) $row->montantTotalHt,
                'facturesReglees' => (int) $row->facturesReglees,
                'montantRegle' => (float) $row->montantRegle,
                'facturesEnAttente' => (int) $row->facturesEnAttente,
                'montantEnAttente' => (float) $row->montantEnAttente,
                'facturesEnRetard' => (int) $row->facturesEnRetard,
                'montantEnRetard' => (float) $row->montantEnRetard,
            ];
        });
    }
}

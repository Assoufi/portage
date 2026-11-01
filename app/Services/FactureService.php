<?php

namespace App\Services;

use App\Models\DetailFacture;
use App\Models\Facture;
use App\Support\NumeroRetry;
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
        $totalFactures = Facture::count();
        $montantTotal = Facture::sum('montant_ttc');
        $montantTotalHt = Facture::sum('total_ht');
        $facturesReglees = Facture::reglee()->count();
        $montantRegle = Facture::reglee()->sum('montant_ttc');
        $facturesEnAttente = Facture::nonReglee()->actif()->count();
        $montantEnAttente = Facture::nonReglee()->actif()->sum('montant_ttc');
        $facturesEnRetard = Facture::nonReglee()
            ->actif()
            ->where('date_echeance', '<', now())
            ->count();
        $montantEnRetard = Facture::nonReglee()
            ->actif()
            ->where('date_echeance', '<', now())
            ->sum('montant_ttc');

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

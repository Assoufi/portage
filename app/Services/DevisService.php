<?php

namespace App\Services;

use App\Models\Devis;
use App\Support\NumeroRetry;
use Illuminate\Support\Facades\DB;

class DevisService
{
    public function createDevis(array $data): Devis
    {
        return DB::transaction(function () use ($data) {
            // Numéro saisi par l'utilisateur : la collision doit remonter telle quelle
            if (! empty($data['numero_devis'])) {
                return Devis::create($data)->fresh();
            }

            return NumeroRetry::sauvegarder(
                fn () => Devis::genererNumeroDevis(),
                fn (string $numeroDevis) => Devis::create(array_merge($data, ['numero_devis' => $numeroDevis]))->fresh()
            );
        });
    }

    public function updateDevis(Devis $devis, array $data): Devis
    {
        return DB::transaction(function () use ($devis, $data) {
            $devis->update($data);

            return $devis->fresh();
        });
    }

    public function deleteDevis(Devis $devis): void
    {
        DB::transaction(function () use ($devis) {
            $devis->delete();
        });
    }

    public function getStats(): array
    {
        $totalDevis = Devis::count();
        $montantTotalHt = (float) Devis::selectRaw('COALESCE(SUM(quantite * prix_unitaire), 0) as total')
            ->value('total');
        $devisMoisCourant = Devis::whereMonth('date_devis', now()->month)
            ->whereYear('date_devis', now()->year)
            ->count();
        $totalClients = Devis::distinct()->count('client_id');

        return compact('totalDevis', 'montantTotalHt', 'devisMoisCourant', 'totalClients');
    }
}

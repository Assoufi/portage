<?php

namespace App\Services;

use App\Models\Devis;
use Illuminate\Support\Facades\DB;

class DevisService
{
    public function createDevis(array $data): Devis
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['numero_devis'])) {
                $data['numero_devis'] = Devis::genererNumeroDevis();
            }

            $devis = Devis::create($data);

            return $devis->fresh();
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

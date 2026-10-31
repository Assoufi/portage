<?php

// app/Services/CommandeService.php

namespace App\Services;

use App\Models\Commande;
use Illuminate\Support\Facades\DB;

class CommandeService
{
    public function createCommande(array $data): Commande
    {
        return DB::transaction(function () use ($data) {
            $commande = Commande::create($data);

            return $commande->fresh();
        });
    }

    public function updateCommande(Commande $commande, array $data): Commande
    {
        return DB::transaction(function () use ($commande, $data) {
            $commande->update($data);

            return $commande->fresh();
        });
    }

    public function deleteCommande(Commande $commande): void
    {
        DB::transaction(function () use ($commande) {
            $commande->delete();
        });
    }

    public function getStats(): array
    {
        $totalCommandes = Commande::count();
        $montantTotalHt = (float) Commande::selectRaw('COALESCE(SUM(quantite * tjm), 0) as total')
            ->value('total');
        $commandesMoisCourant = Commande::whereMonth('date_commande', now()->month)
            ->whereYear('date_commande', now()->year)
            ->count();
        $commandesActives = Commande::where('statut', 'ACTIF')->count();

        return compact('totalCommandes', 'montantTotalHt', 'commandesMoisCourant', 'commandesActives');
    }
}

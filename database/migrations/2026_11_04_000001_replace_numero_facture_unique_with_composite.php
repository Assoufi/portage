<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $doublons = DB::table('factures')
            ->selectRaw('fournisseur_id, numero_facture, COUNT(*) AS c')
            ->groupBy('fournisseur_id', 'numero_facture')
            ->having('c', '>', 1)
            ->count();

        if ($doublons > 0) {
            throw new RuntimeException(
                "Doublons (fournisseur_id, numero_facture) detects ({$doublons}) : ".
                'traiter les doublons avant de relancer la migration.'
            );
        }

        Schema::table('factures', function ($table) {
            $table->dropUnique('factures_numero_facture_unique');
        });

        // Unique par fournisseur : un meme numero chez deux fournisseurs est autorise.
        // Les lignes soft-supprimees restent incluses dans l'index (comportement voulu,
        // conforme au compteur per-fournisseur de Facture::genererNumeroFacturePourFournisseur).
        Schema::table('factures', function ($table) {
            $table->unique(['fournisseur_id', 'numero_facture'], 'factures_fournisseur_numero_facture_unique');
        });
    }

    public function down(): void
    {
        // Attention : la re-creation de l'UNIQUE global echouera si des fournisseurs
        // partagent desormais un meme numero_facture.
        Schema::table('factures', function ($table) {
            $table->dropUnique('factures_fournisseur_numero_facture_unique');
        });

        Schema::table('factures', function ($table) {
            $table->unique('numero_facture', 'factures_numero_facture_unique');
        });
    }
};

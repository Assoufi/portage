<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_factures', function ($table) {
            // La column fut a l'origine 'tva' (taux par defaut 20.00) puis renommee
            // 'montant_tva' qui stocke desormais le MONTANT de TVA.
            // decimal(5,2) (max 999.99) etait insuffisant : 6 838 lignes des imports
            // precedent ont pri ete ecrasees a 999.99 (mode SQL non strict).
            $table->decimal('montant_tva', 12, 2)->default(0)->change();
        });

        // Recalcul du montant TVA a partir du taux des factures et du montant HT,
        // puis remise en coherence du TTC (montant_ht + montant_tva).
        DB::statement(
            'UPDATE detail_factures df
             JOIN factures f ON f.id = df.facture_id
             SET df.montant_tva = ROUND(df.montant_ht * COALESCE(f.taux_tva, 0) / 100, 2),
                 df.montant_ttc = df.montant_ht + ROUND(df.montant_ht * COALESCE(f.taux_tva, 0) / 100, 2)'
        );
    }

    public function down(): void
    {
        // Retour possible uniquement si aucun montant TVA ne depasse 999.99.
        Schema::table('detail_factures', function ($table) {
            $table->decimal('montant_tva', 5, 2)->default(20.00)->change();
        });
    }
};

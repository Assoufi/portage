<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Les donnees reelles contiennent 55+ cas de numeros de facture reutilises
        // par un meme fournisseur (clients/dates differents) : l'UNIQUE
        // (fournisseur_id, numero_facture) bloque l'import. L'unicite des
        // numeros generes reste garante par app/Models/Facture.php (max+1 par
        // fournisseur) et la validation FactureRequest pour les saisies manuelles.
        Schema::table('factures', function ($table) {
            $table->dropUnique('factures_fournisseur_numero_facture_unique');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function ($table) {
            $table->unique(['fournisseur_id', 'numero_facture'], 'factures_fournisseur_numero_facture_unique');
        });
    }
};

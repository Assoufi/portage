<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->index('nom', 'consultants_nom_index');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->index(
                ['consultant_id', 'client_id', 'fournisseur_id', 'date_facture'],
                'factures_rattachement_index'
            );
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->index(
                ['consultant_id', 'client_id', 'fournisseur_id', 'date_debut', 'date_fin'],
                'missions_rattachement_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex('missions_rattachement_index');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropIndex('factures_rattachement_index');
        });

        Schema::table('consultants', function (Blueprint $table) {
            $table->dropIndex('consultants_nom_index');
        });
    }
};

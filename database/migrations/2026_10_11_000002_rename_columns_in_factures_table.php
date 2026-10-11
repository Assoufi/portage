<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->renameColumn('tva', 'taux_tva');
            $table->renameColumn('montant', 'montant_ttc');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->renameColumn('taux_tva', 'tva');
            $table->renameColumn('montant_ttc', 'montant');
        });
    }
};
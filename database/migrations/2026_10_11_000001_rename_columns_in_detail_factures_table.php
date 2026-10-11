<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_factures', function (Blueprint $table) {
            $table->renameColumn('total_ht', 'montant_ht');
            $table->renameColumn('tva', 'montant_tva');
        });
    }

    public function down(): void
    {
        Schema::table('detail_factures', function (Blueprint $table) {
            $table->renameColumn('montant_ht', 'total_ht');
            $table->renameColumn('montant_tva', 'tva');
        });
    }
};

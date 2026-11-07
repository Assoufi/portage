<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quantités décimales (ex. 21,5 jours) : l'import Excel et les factures
        // manuelles doivent conserver la partie décimale.
        Schema::table('detail_factures', function (Blueprint $table) {
            $table->decimal('quantite', 15, 2)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('detail_factures', function (Blueprint $table) {
            $table->integer('quantite')->default(1)->change();
        });
    }
};

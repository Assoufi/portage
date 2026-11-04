<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'mode_paiement')) {
                $table->enum('mode_paiement', ['virement', 'cheque', 'especes'])
                    ->default('virement')
                    ->nullable()
                    ->after('delai_paiement');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'mode_paiement')) {
                $table->dropColumn('mode_paiement');
            }
        });
    }
};

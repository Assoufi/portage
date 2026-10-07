<?php
// database/migrations/2026_09_19_000001_add_optional_fields_to_clients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('adresse_facturation')->nullable()->after('adresse');
            $table->unsignedInteger('delai_paiement')->nullable()->after('devise'); // en jours
            $table->text('remarques')->nullable()->after('delai_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['adresse_facturation', 'delai_paiement', 'remarques']);
        });
    }
};

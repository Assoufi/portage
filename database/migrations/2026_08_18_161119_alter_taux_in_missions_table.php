<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix missions.taux: revert from string back to decimal
        Schema::table('missions', function (Blueprint $table) {
            $table->decimal('taux', 5, 2)->nullable()->change();
        });

        // Add DB defaults so models don't need boot() retrieved callbacks
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('tva', 5, 2)->default(20.00)->change();
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->decimal('taux', 5, 2)->default(0.00)->change();
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('taux')->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('tva', 5, 2)->nullable()->change();
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->decimal('taux', 5, 2)->nullable()->change();
        });
    }
};

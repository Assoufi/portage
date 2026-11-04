<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rendre email nullable sur consultants : l'index unique fonctionnel crée dans la
// migration 2026_11_03_000000 convertit les emails vides/NULL en NULL, ce qui
// autorise plusieurs consultants sans email (le stockage NULL est alors requis).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('email', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('email', 50)->change();
        });
    }
};

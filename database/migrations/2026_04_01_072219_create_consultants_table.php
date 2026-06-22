<?php
// database/migrations/2026_01_01_000001_create_consultants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 255);
            $table->string('email', 50)->unique();
            $table->string('tel', 20);
            $table->string('rib', 50)->nullable();
            $table->enum('mode_paiement', ['virement', 'cheque', 'especes'])->default('virement');
            $table->boolean('statut')->default(true);
            $table->timestamps();
            $table->softDeletes(); // Pour archivage sécurisé
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultants');
    }
};
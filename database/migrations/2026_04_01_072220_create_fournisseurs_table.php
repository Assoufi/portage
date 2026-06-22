<?php
// database/migrations/2026_01_01_000003_create_fournisseurs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->text('adresse');
            $table->string('email', 50)->unique();
            $table->string('ice', 15)->unique(); // Exactement 15 caractères
            $table->decimal('taux', 10, 2)->default(0.00);
            $table->boolean('statut')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('ice');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
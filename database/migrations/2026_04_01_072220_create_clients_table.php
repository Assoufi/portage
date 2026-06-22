<?php
// database/migrations/2026_01_01_000002_create_clients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->text('adresse');
            $table->string('email', 50)->unique();
            $table->string('ice', 15)->unique(); // Exactement 15 caractères
            $table->decimal('tva', 5, 2)->default(20.00);
            $table->string('devise', 3)->default('MAD');
            $table->boolean('statut')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimiser les recherches
            $table->index('ice');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
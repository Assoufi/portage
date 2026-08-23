<?php
// database/migrations/2026_01_01_000004_create_missions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères
            $table->foreignId('consultant_id')
                  ->constrained('consultants')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
                  
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
                  
            $table->foreignId('fournisseur_id')
                  ->constrained('fournisseurs')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            // Champs métier 
            $table->string('titre', 50)->nullable();
            $table->string('formule', 50)->nullable();
            $table->decimal('taux', 5, 2)->nullable();
            $table->decimal('tjm', 10, 2)->default(0); // Taux Journalier Moyen
            $table->decimal('prix_vente', 10, 2)->default(0);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->integer('delai_paiement')->default(30); // en jours
            $table->text('remarques')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimiser les requêtes
            $table->index('consultant_id');
            $table->index('client_id');
            $table->index('fournisseur_id');
            $table->index('date_debut');
            $table->index('date_fin');
            $table->index(['consultant_id', 'date_debut']);
            
            // Contrainte de validation : date_fin doit être >= date_debut (gérée au niveau applicatif)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
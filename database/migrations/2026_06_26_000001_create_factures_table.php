<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict')->onUpdate('cascade');
            $table->string('numero_facture', 50)->unique();
            $table->string('numero_bcm', 50)->nullable();
            $table->date('date_facture');
            $table->string('designation')->nullable();
            $table->integer('quantite')->nullable();
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->decimal('total_ht', 12, 2)->nullable();
            $table->decimal('tva', 5, 2)->default(20.00);
            $table->decimal('montant', 12, 2);
            $table->date('date_echeance')->nullable();
            $table->date('date_reception')->nullable();
            $table->date('date_paiement')->nullable();
            $table->string('mode_paiement', 50)->nullable();
            $table->string('reference_paiement', 100)->nullable();
            $table->date('date_reglement')->nullable();
            $table->string('mode_reglement', 50)->nullable();
            $table->string('reference_reglement', 100)->nullable();
            $table->string('beneficiaire')->nullable();
            $table->text('remarques')->nullable();
            $table->boolean('statut')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('numero_facture');
            $table->index('date_facture');
            $table->index(['fournisseur_id', 'client_id']);
        });

        Schema::create('detail_factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->onDelete('cascade')->onUpdate('cascade');
            $table->string('designation');
            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('total_ht', 12, 2);
            $table->decimal('tva', 5, 2)->default(20.00);
            $table->decimal('montant_ttc', 12, 2);
            $table->timestamps();
            $table->index('facture_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_factures');
        Schema::dropIfExists('factures');
    }
};

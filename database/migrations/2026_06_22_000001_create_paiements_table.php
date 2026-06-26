<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('mission_id')->nullable()->constrained('missions')->onDelete('restrict')->onUpdate('cascade');
            $table->string('reference', 50)->unique();
            $table->decimal('montant', 12, 2);
            $table->decimal('montant_recu', 12, 2)->nullable();
            $table->date('date_paiement');
            $table->enum('mode_paiement', ['virement', 'cheque', 'especes', 'carte'])->default('virement');
            $table->text('remarques')->nullable();
            $table->boolean('statut')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('reference');
            $table->index('date_paiement');
            $table->index(['client_id', 'fournisseur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};

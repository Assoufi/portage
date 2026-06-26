<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repartitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained('paiements')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('consultant_id')->constrained('consultants')->onDelete('restrict')->onUpdate('cascade');
            $table->decimal('montant', 12, 2);
            $table->date('date_paiement');
            $table->text('remarques')->nullable();
            $table->string('rib', 50)->nullable();
            $table->string('banque', 100)->nullable();
            $table->enum('mode_paiement', ['virement', 'cheque', 'especes'])->default('virement');
            $table->string('telephone', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['paiement_id', 'consultant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repartitions');
    }
};

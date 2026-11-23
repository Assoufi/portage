<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une clé étrangère exige des tables InnoDB côté référencé.
        foreach (['factures', 'fournisseurs', 'clients', 'consultants', 'missions'] as $referenced) {
            $engine = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$referenced]
            )?->engine;

            if ($engine && strcasecmp($engine, 'InnoDB') !== 0) {
                DB::statement("ALTER TABLE `{$referenced}` ENGINE=InnoDB");
            }
        }

        Schema::create('avoirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')
                ->constrained('factures')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('fournisseur_id')
                ->constrained('fournisseurs')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('client_id')
                ->constrained('clients')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('consultant_id')
                ->nullable()
                ->constrained('consultants')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('mission_id')
                ->nullable()
                ->constrained('missions')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('numero_avoir', 50)->unique();
            $table->date('date_avoir');
            $table->string('type', 20)->default('partiel');
            $table->text('motif')->nullable();
            $table->decimal('total_ht', 12, 2)->nullable();
            $table->decimal('taux_tva', 5, 2)->default(20.00);
            $table->decimal('montant_ttc', 12, 2)->default(0);
            $table->text('remarques')->nullable();
            $table->boolean('statut')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('date_avoir');
            $table->index(['facture_id', 'client_id']);
        });

        Schema::create('detail_avoirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avoir_id')
                ->constrained('avoirs')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('designation');
            $table->decimal('quantite', 15, 2)->default(1);
            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->decimal('montant_ht', 12, 2);
            $table->decimal('montant_tva', 12, 2);
            $table->decimal('montant_ttc', 12, 2)->default(0);
            $table->timestamps();

            $table->index('avoir_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_avoirs');
        Schema::dropIfExists('avoirs');
    }
};

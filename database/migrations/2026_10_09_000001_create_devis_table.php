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
        // Le serveur peut héberger des tables historiques en MyISAM :
        // on convertit uniquement les tables référencées par devis.
        foreach (['fournisseurs', 'clients', 'missions'] as $referenced) {
            $engine = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$referenced]
            )->engine ?? null;

            if ($engine && strcasecmp($engine, 'InnoDB') !== 0) {
                DB::statement("ALTER TABLE `{$referenced}` ENGINE=InnoDB");
            }
        }

        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')
                ->constrained('fournisseurs')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('client_id')
                ->constrained('clients')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('mission_id')
                ->nullable()
                ->constrained('missions')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('numero_devis', 50)->unique();
            $table->date('date_devis');
            $table->text('description')->nullable();
            $table->decimal('quantite', 15, 2)->default(1);
            $table->decimal('prix_unitaire', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('date_devis');
            $table->index('client_id');
            $table->index('mission_id');
            $table->index('fournisseur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};

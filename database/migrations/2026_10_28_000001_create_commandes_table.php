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
        // on convertit uniquement les tables référencées par commandes.
        foreach (['missions'] as $referenced) {
            $engine = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$referenced]
            )->engine ?? null;

            if ($engine && strcasecmp($engine, 'InnoDB') !== 0) {
                DB::statement("ALTER TABLE `{$referenced}` ENGINE=InnoDB");
            }
        }

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')
                ->constrained('missions')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            // Le fournisseur n'est pas stocké sur la commande :
            // il est dérivé de la mission liée (via mission->fournisseur).
            $table->string('num_commande', 50)->unique();
            $table->date('date_commande');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('quantite', 15, 2)->default(1);
            $table->decimal('tjm', 15, 2)->default(0);
            $table->string('statut', 20)->default('ACTIF');
            $table->timestamps();

            $table->index('date_commande');
            $table->index('mission_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};

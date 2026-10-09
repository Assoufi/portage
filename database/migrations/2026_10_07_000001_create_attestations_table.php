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
        // on convertit uniquement les tables référencées par attestations.
        foreach (['consultants', 'missions'] as $referenced) {
            $engine = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$referenced]
            )->engine ?? null;

            if ($engine && strcasecmp($engine, 'InnoDB') !== 0) {
                DB::statement("ALTER TABLE `{$referenced}` ENGINE=InnoDB");
            }
        }

        Schema::create('attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')
                ->constrained('consultants')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('mission_id')
                ->constrained('missions')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('fonction', 100);
            $table->date('date_attestation');
            $table->date('date_signature');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('client', 100);
            $table->timestamps();
            $table->softDeletes();

            $table->index('consultant_id');
            $table->index('mission_id');
            $table->index('date_attestation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};

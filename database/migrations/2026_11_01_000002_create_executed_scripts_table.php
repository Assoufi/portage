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
        // on convertit uniquement les tables référencées par executed_scripts.
        $engine = DB::selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['users']
        )->engine ?? null;

        if ($engine && strcasecmp($engine, 'InnoDB') !== 0) {
            DB::statement('ALTER TABLE `users` ENGINE=InnoDB');
        }

        Schema::create('executed_scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('filename');
            $table->string('stored_path', 512);
            $table->string('checksum', 64);
            $table->json('detected_risks')->nullable();
            // pending : en attente de confirmation / blocked : requêtes dangereuses
            // détectées / completed : exécuté avec succès / failed : échec à l'exécution
            $table->string('status', 20)->default('pending');
            $table->longText('output')->nullable();
            $table->longText('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedBigInteger('affected_rows')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executed_scripts');
    }
};

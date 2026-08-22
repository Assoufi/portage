<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('contact', 200)->nullable()->after('statut');
            $table->string('periodicite', 30)->nullable()->after('contact');
            $table->string('mode_livraison', 30)->nullable()->after('periodicite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['contact', 'periodicite', 'mode_livraison']);
        });
    }
};

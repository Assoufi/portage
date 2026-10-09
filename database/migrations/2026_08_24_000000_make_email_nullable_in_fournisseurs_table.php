<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La migration de création a évolué : email est déjà nullable et non unique
        // sur une base fraîche. On ne supprime l'index unique que s'il existe.
        $indexExiste = count(DB::select(
            "SHOW INDEX FROM fournisseurs WHERE Key_name = 'fournisseurs_email_unique'"
        )) > 0;

        Schema::table('fournisseurs', function (Blueprint $table) use ($indexExiste) {
            if ($indexExiste) {
                $table->dropUnique('fournisseurs_email_unique');
            }

            $table->string('email', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('email', 50)->unique()->change();
        });
    }
};

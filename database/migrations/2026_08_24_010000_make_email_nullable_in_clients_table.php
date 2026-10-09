<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La migration de création a évolué : email est déjà nullable et non indexé
        // sur une base fraîche. On ne supprime l'index que s'il existe.
        $indexExiste = count(DB::select(
            "SHOW INDEX FROM clients WHERE Column_name = 'email' AND Key_name <> 'PRIMARY'"
        )) > 0;

        Schema::table('clients', function (Blueprint $table) use ($indexExiste) {
            if ($indexExiste) {
                $table->dropIndex(['email']);
            }

            $table->string('email', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email', 50)->unique()->change();
        });
    }
};

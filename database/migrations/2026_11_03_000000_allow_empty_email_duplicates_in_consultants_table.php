<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Permettre plusieurs consultants sans email (valeur NULL ou vide) :
// MySQL autorise plusieurs NULL dans un index unique, mais pas plusieurs ''.
// On remplace donc l'index unique classique par un index unique fonctionnel
// qui convertit vide/NULL en NULL, tout en conservant l'unicité des emails réels.
return new class extends Migration
{
    private const INDEX_NAME = 'consultants_email_unique';

    public function up(): void
    {
        // 1. Normaliser les chaînes vides en NULL
        DB::table('consultants')
            ->whereRaw("TRIM(email) = ''")
            ->update(['email' => null]);

        // 2. Supprimer l'index unique classique s'il existe
        $indexExiste = count(DB::select(
            "SHOW INDEX FROM consultants WHERE Key_name = '".self::INDEX_NAME."'"
        )) > 0;
        if ($indexExiste) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }

        // 3. Index unique fonctionnel :
        //    - email NULL ou vide   -> NULL   (plusieurs occurrences autorisées)
        //    - email renseigné      -> LOWER(TRIM(email)) (unicité conservée)
        DB::statement(
            'ALTER TABLE consultants ADD UNIQUE INDEX '.self::INDEX_NAME.
            " ((IF(email IS NULL OR TRIM(email) = '', NULL, LOWER(TRIM(email)))))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE consultants DROP INDEX '.self::INDEX_NAME);
        Schema::table('consultants', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};

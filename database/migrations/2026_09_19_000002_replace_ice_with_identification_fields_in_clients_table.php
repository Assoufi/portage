<?php
// database/migrations/2026_09_19_000002_replace_ice_with_identification_fields_in_clients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On ne peut pas déréférencer les index et modifier/déposer la colonne
        // dans le même appel, on procède donc en plusieurs étapes.
        // Les index existants peuvent varier selon l'historique de la base.
        $existingIndexes = collect(DB::select('SHOW INDEX FROM clients'))->pluck('Key_name')->unique();

        Schema::table('clients', function (Blueprint $table) use ($existingIndexes) {
            if ($existingIndexes->contains('clients_ice_unique')) {
                $table->dropUnique('clients_ice_unique');
            }
            if ($existingIndexes->contains('clients_ice_index')) {
                $table->dropIndex('clients_ice_index');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('type_identification', 50)->default('ICE')->after('email');
            $table->string('num_identification', 50)->nullable()->after('type_identification');
        });

        // Conserver les données : l'ancien ICE devient num_identification
        DB::table('clients')
            ->whereNotNull('ice')
            ->update(['num_identification' => DB::raw('ice')]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('ice');
            $table->index('num_identification');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('ice', 15)->nullable()->after('email');
        });

        // Restaurer ice depuis num_identification (format ICE valide uniquement)
        DB::table('clients')
            ->whereNotNull('num_identification')
            ->whereRaw("num_identification REGEXP '^[A-Z0-9]{15}$'")
            ->update(['ice' => DB::raw('num_identification')]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['num_identification']);
            $table->dropColumn(['type_identification', 'num_identification']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->unique('ice');
            $table->index('ice');
        });
    }
};

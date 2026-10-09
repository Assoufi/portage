<?php

// database/migrations/2026_10_09_000002_add_notification_fields_to_clients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonnes ajoutées par cette migration (hors mode_livraison / periodicite
     * qui ont pu être créées manuellement en base et doivent rester idempotentes).
     */
    private array $ajoutees = ['telephone', 'notifyto', 'notifycc'];

    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'periodicite')) {
                $table->string('periodicite', 50)->nullable()->after('remarques');
            }

            if (! Schema::hasColumn('clients', 'mode_livraison')) {
                $table->json('mode_livraison')->nullable()->after('periodicite');
            }

            if (! Schema::hasColumn('clients', 'telephone')) {
                $table->string('telephone', 20)->nullable()->after('mode_livraison');
            }

            if (! Schema::hasColumn('clients', 'notifyto')) {
                $table->string('notifyto', 250)->nullable()->after('telephone');
            }

            if (! Schema::hasColumn('clients', 'notifycc')) {
                $table->string('notifycc', 250)->nullable()->after('notifyto');
            }
        });

        // La colonne existe peut-être déjà en VARCHAR (création manuelle) :
        // on la convertit en JSON pour supporter le stockage multi-valeurs.
        $type = DB::selectOne(
            'SELECT DATA_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['clients', 'mode_livraison']
        )->type ?? null;

        if ($type && strcasecmp($type, 'json') !== 0) {
            DB::statement('ALTER TABLE `clients` MODIFY `mode_livraison` JSON NULL');
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach ($this->ajoutees as $colonne) {
                if (Schema::hasColumn('clients', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });

        $type = DB::selectOne(
            'SELECT DATA_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['clients', 'mode_livraison']
        )->type ?? null;

        if ($type && strcasecmp($type, 'json') === 0) {
            DB::statement('ALTER TABLE `clients` MODIFY `mode_livraison` VARCHAR(50) NULL');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->date('date_fin')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('attestations')->whereNull('date_fin')->delete();

        Schema::table('attestations', function (Blueprint $table) {
            $table->date('date_fin')->nullable(false)->change();
        });
    }
};

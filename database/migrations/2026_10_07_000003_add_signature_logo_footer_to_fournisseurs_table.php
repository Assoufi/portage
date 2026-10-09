<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('rib');
            $table->string('logo')->nullable()->after('signature');
            $table->text('footer')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['signature', 'logo', 'footer']);
        });
    }
};

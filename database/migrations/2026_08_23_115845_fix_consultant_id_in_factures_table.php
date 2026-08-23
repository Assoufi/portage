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
        Schema::table('factures', function (Blueprint $table) {
            $table->foreignId('consultant_id')->nullable()->change()->constrained('consultants')->onDelete('set null')->onUpdate('cascade');
            $table->index(['consultant_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropForeign(['consultant_id']);
            $table->dropIndex(['consultant_id', 'client_id']);
            $table->unsignedBigInteger('consultant_id')->nullable(false)->change();
        });
    }
};

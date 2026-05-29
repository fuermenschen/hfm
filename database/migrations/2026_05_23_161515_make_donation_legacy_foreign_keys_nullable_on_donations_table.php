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
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_id']);
            $table->dropForeign(['athlete_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_id')->nullable()->change();
            $table->unsignedBigInteger('athlete_id')->nullable()->change();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donor_id')->references('id')->on('donors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('athlete_id')->references('id')->on('athletes')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_id']);
            $table->dropForeign(['athlete_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_id')->nullable(false)->change();
            $table->unsignedBigInteger('athlete_id')->nullable(false)->change();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donor_id')->references('id')->on('donors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('athlete_id')->references('id')->on('athletes')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_id']);
            $table->dropForeign(['athlete_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['donor_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_id')->nullable()->after('id');
            $table->unsignedBigInteger('athlete_id')->nullable()->after('donor_id');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donor_id')->references('id')->on('donors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('athlete_id')->references('id')->on('athletes')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
};

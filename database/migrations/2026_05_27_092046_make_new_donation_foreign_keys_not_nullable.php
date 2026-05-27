<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_external_user_id']);
            $table->dropForeign(['athlete_registration_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_external_user_id')->nullable(false)->change();
            $table->unsignedBigInteger('athlete_registration_id')->nullable(false)->change();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donor_external_user_id')->references('id')->on('external_users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('athlete_registration_id')->references('id')->on('athlete_registrations')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_external_user_id']);
            $table->dropForeign(['athlete_registration_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedBigInteger('donor_external_user_id')->nullable()->change();
            $table->unsignedBigInteger('athlete_registration_id')->nullable()->change();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donor_external_user_id')->references('id')->on('external_users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('athlete_registration_id')->references('id')->on('athlete_registrations')->cascadeOnUpdate()->restrictOnDelete();
        });
    }
};

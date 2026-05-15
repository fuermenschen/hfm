<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->foreignId('donor_external_user_id')->nullable()->after('donor_id')->constrained('external_users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('athlete_registration_id')->nullable()->after('athlete_id')->constrained('athlete_registrations')->cascadeOnUpdate()->restrictOnDelete();

            $table->index(['donor_external_user_id']);
            $table->index(['athlete_registration_id']);
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_external_user_id']);
            $table->dropForeign(['athlete_registration_id']);
            $table->dropColumn(['donor_external_user_id', 'athlete_registration_id']);
        });
    }
};

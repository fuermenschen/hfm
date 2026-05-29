<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_users', function (Blueprint $table) {
            $table->dropUnique('external_users_legacy_athlete_id_unique');
            $table->dropUnique('external_users_legacy_donor_id_unique');
        });

        Schema::table('external_users', function (Blueprint $table) {
            $table->dropColumn(['legacy_athlete_id', 'legacy_donor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('external_users', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_athlete_id')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('legacy_donor_id')->nullable()->after('legacy_athlete_id');
        });

        Schema::table('external_users', function (Blueprint $table) {
            $table->unique('legacy_athlete_id');
            $table->unique('legacy_donor_id');
        });
    }
};

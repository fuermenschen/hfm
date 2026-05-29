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
        // Athletes
        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('sport_type_id')->constrained('sport_types');
            $table->foreignId('partner_id')->constrained('partners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Athletes
        Schema::table('athletes', function (Blueprint $table) {
            if (Schema::hasColumn('athletes', 'sport_type_id')) {
                // Drop FK first (uses conventional name athletes_sport_type_id_foreign)
                $table->dropForeign(['sport_type_id']);
                $table->dropColumn('sport_type_id');
            }

            if (Schema::hasColumn('athletes', 'partner_id')) {
                $table->dropForeign(['partner_id']);
                $table->dropColumn('partner_id');
            }
        });
    }
};

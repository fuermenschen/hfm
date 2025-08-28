<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donators', function (Blueprint $table) {
            // Add country_of_residence after city
            $table->string('country_of_residence', 2)->after('city')->default('CH')->comment('ISO 3166-1 alpha-2 country code');
        });

        // Update phone_number: replace leading '0' with '+41'
        DB::table('donators')
            ->where('phone_number', 'like', '0%')
            ->update([
                // SQLite: use '||' for concat and substr() for substring
                'phone_number' => DB::raw("'+41 ' || substr(phone_number, 2)"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donators', function (Blueprint $table) {
            $table->dropColumn('country_of_residence');
        });

        // Phone number is not reverted to its original format
    }
};

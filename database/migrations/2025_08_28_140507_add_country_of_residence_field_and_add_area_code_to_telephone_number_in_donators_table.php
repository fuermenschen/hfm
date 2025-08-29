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

        // Ensure existing rows have a value
        DB::table('donators')->whereNull('country_of_residence')->update(['country_of_residence' => 'CH']);

        // Update phone_number: replace leading '0' with '+41 '
        $driver = DB::getDriverName();

        // Build a DB-agnostic expression to prepend '+41 ' and drop the leading 0
        $expression = match ($driver) {
            // SQLite and PostgreSQL support || and substr(text, from)
            'sqlite', 'pgsql' => DB::raw("'+41 ' || substr(phone_number, 2)"),

            // MySQL / MariaDB
            'mysql' => DB::raw("CONCAT('+41 ', SUBSTRING(phone_number, 2))"),

            // SQL Server
            'sqlsrv' => DB::raw("'+41 ' + SUBSTRING(phone_number, 2, LEN(phone_number) - 1)"),

            // Fallback to a safe no-op (won't update numbers) if unknown driver
            default => null,
        };

        if ($expression !== null) {
            DB::table('donators')
                ->where('phone_number', 'like', '0%')
                ->update([
                    'phone_number' => $expression,
                ]);
        }
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

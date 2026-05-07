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
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('athletes', function (Blueprint $table): void {
                $table->foreignId('donation_event_id')
                    ->nullable()
                    ->after('partner_id')
                    ->constrained('donation_events');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('athletes', function (Blueprint $table): void {
                $table->dropForeign(['donation_event_id']);
                $table->dropColumn('donation_event_id');
            });
        });
    }
};

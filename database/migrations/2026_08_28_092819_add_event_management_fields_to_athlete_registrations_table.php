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
        Schema::table('athlete_registrations', function (Blueprint $table) {
            $table->unsignedSmallInteger('start_number')->nullable()->after('rounds_done');
            $table->string('event_state')->default('not_started')->after('start_number');
            $table->unique(['donation_event_id', 'start_number']);
        });
    }

    public function down(): void
    {
        Schema::table('athlete_registrations', function (Blueprint $table) {
            $table->dropUnique(['donation_event_id', 'start_number']);
            $table->dropColumn(['start_number', 'event_state']);
        });
    }
};

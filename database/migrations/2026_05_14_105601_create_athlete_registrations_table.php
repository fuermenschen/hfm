<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_event_id')->constrained('donation_events')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('external_user_id')->constrained('external_users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('sport_type_id')->constrained('sport_types')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->unsignedTinyInteger('rounds_estimated')->default(0);
            $table->unsignedTinyInteger('rounds_done')->default(0);
            $table->text('comment')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->unique(['donation_event_id', 'external_user_id']);
            $table->index(['donation_event_id', 'verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_registrations');
    }
};

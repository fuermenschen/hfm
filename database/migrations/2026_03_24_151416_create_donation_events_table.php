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
        Schema::create('donation_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('timezone')->default('Europe/Zurich');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('athlete_registration_closes_at')->nullable();
            $table->dateTime('donor_registration_closes_at')->nullable();
            $table->string('location_name')->nullable();
            $table->string('location_street')->nullable();
            $table->string('location_postal_code')->nullable();
            $table->string('location_city');
            $table->string('location_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->json('content')->nullable();
            $table->timestamps();

            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('athlete_registration_closes_at');
            $table->index('donor_registration_closes_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_events');
    }
};

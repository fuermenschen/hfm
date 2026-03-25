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
            $table->dateTimeTz('starts_at');
            $table->dateTimeTz('ends_at');
            $table->dateTimeTz('registration_opens_at')->nullable();
            $table->dateTimeTz('athlete_registration_closes_at')->nullable();
            $table->dateTimeTz('donor_registration_closes_at')->nullable();
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

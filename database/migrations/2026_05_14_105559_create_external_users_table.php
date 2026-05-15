<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country_of_residence')->default('CH');
            $table->string('phone_number')->nullable();
            $table->string('email')->unique();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('legacy_athlete_id')->nullable()->unique();
            $table->unsignedBigInteger('legacy_donor_id')->nullable()->unique();
            $table->string('public_id', 6)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_users');
    }
};

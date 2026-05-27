<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('athletes');
        Schema::dropIfExists('donors');
    }

    public function down(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->unsignedSmallInteger('zip_code');
            $table->string('city');
            $table->string('phone_number');
            $table->string('email')->unique();
            $table->boolean('adult');
            $table->unsignedTinyInteger('rounds_estimated');
            $table->unsignedTinyInteger('rounds_done')->default(0);
            $table->text('comment')->nullable();
            $table->unsignedInteger('public_id')->unique()->nullable();
            $table->string('login_token')->unique()->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->foreignId('sport_type_id')->constrained('sport_types');
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('donation_event_id')->nullable()->constrained('donation_events');
        });

        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->text('zip_code')->nullable();
            $table->string('city');
            $table->string('country_of_residence', 2)->default('CH');
            $table->string('phone_number');
            $table->string('email');
            $table->string('login_token')->unique()->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->json('webling_data')->nullable();
            $table->timestamp('invoice_reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }
};

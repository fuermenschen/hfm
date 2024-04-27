<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("donators", function (Blueprint $table) {
            $table->id();
            $table->string("first_name");
            $table->string("last_name");
            $table->string("address");
            $table->unsignedInteger("zip_code");
            $table->string("city");
            $table->string("phone_number");
            $table->string("email");
            $table->unsignedBigInteger("athlete_id")->nullable();
            $table->float("amount_per_round");
            $table->float("amount_max");
            $table->float("amount_min");
            $table->timestamp("email_verified_at")->nullable();
            $table->text("comment")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("donators");
    }
};

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
        Schema::create("athletes", function (Blueprint $table) {
            $table->id();
            $table->string("first_name");
            $table->string("last_name");
            $table->string("address");
            $table->unsignedSmallInteger("zip_code");
            $table->string("city");
            $table->string("phone_number");
            $table->string("email")->unique();
            $table->boolean("adult");
            $table->foreignId("sport_type_id")->constrained();
            $table->unsignedTinyInteger("rounds_estimated");
            $table->unsignedTinyInteger("rounds_done")->default(0);
            $table->foreignId("partner_id")->constrained();
            $table->text("comment")->nullable();
            $table->unsignedInteger("public_id")->unique()->nullable();
            $table->string("login_token")->unique()->nullable();
            $table->boolean("verified")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("athletes");
    }
};

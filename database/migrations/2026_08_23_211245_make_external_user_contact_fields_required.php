<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_users', function (Blueprint $table) {
            $table->string('address')->nullable(false)->change();
            $table->string('zip_code')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('external_users', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('zip_code')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
        });
    }
};

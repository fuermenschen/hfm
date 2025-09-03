<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('association_members');
    }

    public function down(): void
    {
        if (! Schema::hasTable('association_members')) {
            Schema::create('association_members', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('address');
                $table->unsignedSmallInteger('zip_code');
                $table->string('city');
                $table->string('phone_number');
                $table->string('email')->unique();
                $table->string('company_name')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }
    }
};

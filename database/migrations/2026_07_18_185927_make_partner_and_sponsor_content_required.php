<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('logo_light_filename')->nullable(false)->change();
            $table->string('logo_dark_filename')->nullable(false)->change();
            $table->text('beneficiary_blurb')->nullable(false)->change();
            $table->string('url')->nullable(false)->change();
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
            $table->string('url')->nullable(false)->change();
        });

        Schema::table('donation_event_sponsor', function (Blueprint $table) {
            $table->text('contribution_text')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('logo_light_filename')->nullable()->change();
            $table->string('logo_dark_filename')->nullable()->change();
            $table->text('beneficiary_blurb')->nullable()->change();
            $table->string('url')->nullable()->change();
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('url')->nullable()->change();
        });

        Schema::table('donation_event_sponsor', function (Blueprint $table) {
            $table->text('contribution_text')->nullable()->change();
        });
    }
};

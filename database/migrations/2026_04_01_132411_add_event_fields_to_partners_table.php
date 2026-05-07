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
        Schema::table('partners', function (Blueprint $table) {
            $table->string('logo_light_filename')->nullable()->after('name');
            $table->string('logo_dark_filename')->nullable()->after('logo_light_filename');
            $table->text('beneficiary_blurb')->nullable()->after('logo_dark_filename');
            $table->string('url')->nullable()->after('beneficiary_blurb');
            $table->unique('name', 'partners_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropUnique('partners_name_unique');
            $table->dropColumn([
                'logo_light_filename',
                'logo_dark_filename',
                'beneficiary_blurb',
                'url',
            ]);
        });
    }
};

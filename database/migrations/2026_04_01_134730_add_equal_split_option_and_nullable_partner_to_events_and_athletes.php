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
        Schema::table('donation_events', function (Blueprint $table) {
            $table->boolean('has_equal_split_option')
                ->default(true)
                ->after('is_published');
        });

        Schema::table('athletes', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->unsignedBigInteger('partner_id')->nullable()->change();
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->unsignedBigInteger('partner_id')->nullable(false)->change();
            $table->foreign('partner_id')->references('id')->on('partners');
        });

        Schema::table('donation_events', function (Blueprint $table) {
            $table->dropColumn('has_equal_split_option');
        });
    }
};

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
        Schema::table('donor_event_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('webling_excess_cents')->nullable();
            $table->unsignedBigInteger('webling_writeoff_cents')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donor_event_invoices', function (Blueprint $table) {
            $table->dropColumn(['webling_excess_cents', 'webling_writeoff_cents']);
        });
    }
};

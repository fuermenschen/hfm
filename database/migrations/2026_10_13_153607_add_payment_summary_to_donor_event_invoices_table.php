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
            $table->unsignedBigInteger('webling_received_cents')->nullable();
            $table->date('webling_payment_date')->nullable();
            $table->string('webling_payment_verification')->default('unverified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donor_event_invoices', function (Blueprint $table) {
            $table->dropColumn(['webling_received_cents', 'webling_payment_date', 'webling_payment_verification']);
        });
    }
};

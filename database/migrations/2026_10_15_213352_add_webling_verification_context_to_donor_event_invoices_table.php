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
        Schema::table('donor_event_invoices', function (Blueprint $table): void {
            $table->json('webling_verification_context')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donor_event_invoices', function (Blueprint $table): void {
            $table->dropColumn('webling_verification_context');
        });
    }
};

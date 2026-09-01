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
        Schema::create('donor_event_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('donation_event_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('webling_debitor_id')->nullable();
            $table->string('webling_invoice_number')->nullable();
            $table->string('webling_state')->nullable();
            $table->date('webling_due_date')->nullable();
            $table->unsignedBigInteger('webling_total_cents')->nullable();
            $table->unsignedBigInteger('webling_remaining_cents')->nullable();
            $table->timestamp('webling_synced_at')->nullable();
            $table->string('pdf_disk')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->timestamp('invoice_reminder_sent_at')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->unsignedBigInteger('source_total_cents')->nullable();
            $table->timestamp('remote_deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['external_user_id', 'donation_event_id']);
            $table->index('donation_event_id');
            $table->index('webling_debitor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_event_invoices');
    }
};

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
        Schema::table('donators', function (Blueprint $table) {
            $table->boolean('invoice_sent')->default(false);
            $table->timestamp('invoice_sent_at')->nullable();
            $table->boolean('invoice_paid')->default(false);
            $table->timestamp('invoice_paid_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donators', function (Blueprint $table) {
            $table->dropColumn('invoice_sent');
            $table->dropColumn('invoice_sent_at');
            $table->dropColumn('invoice_paid');
            $table->dropColumn('invoice_paid_at');
        });
    }
};

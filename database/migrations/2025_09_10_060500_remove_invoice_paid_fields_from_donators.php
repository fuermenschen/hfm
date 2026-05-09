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
        // Remove invoice_paid related columns from donators
        Schema::table('donators', function (Blueprint $table) {
            if (Schema::hasColumn('donators', 'invoice_paid_at')) {
                $table->dropColumn('invoice_paid_at');
            }

            if (Schema::hasColumn('donators', 'invoice_paid')) {
                $table->dropColumn('invoice_paid');
            }

            if (Schema::hasColumn('donators', 'invoice_sent')) {
                $table->dropColumn('invoice_sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donators', function (Blueprint $table) {
            if (! Schema::hasColumn('donators', 'invoice_paid')) {
                $table->boolean('invoice_paid')->default(false);
            }

            if (! Schema::hasColumn('donators', 'invoice_paid_at')) {
                $table->timestamp('invoice_paid_at')->nullable();
            }

            if (! Schema::hasColumn('donators', 'invoice_sent')) {
                $table->boolean('invoice_sent')->default(false);
            }
        });
    }
};

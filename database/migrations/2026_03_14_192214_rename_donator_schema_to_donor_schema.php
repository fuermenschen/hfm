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
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donator_id']);
        });

        Schema::rename('donators', 'donors');

        Schema::table('donations', function (Blueprint $table) {
            $table->renameColumn('donator_id', 'donor_id');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donor_id', 'donations_donor_id_foreign')
                ->references('id')
                ->on('donors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['donor_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->renameColumn('donor_id', 'donator_id');
        });

        Schema::rename('donors', 'donators');

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('donator_id', 'donations_donator_id_foreign')
                ->references('id')
                ->on('donators')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};

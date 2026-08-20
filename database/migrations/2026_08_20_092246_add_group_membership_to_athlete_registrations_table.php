<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athlete_registrations', function (Blueprint $table) {
            $table->foreignId('event_group_id')
                ->nullable()
                ->constrained('event_groups')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('group_membership_status')->nullable();
            $table->string('group_membership_role')->nullable();

            $table->index(
                ['event_group_id', 'group_membership_status'],
                'athlete_registrations_group_membership_index',
            );
        });

    }

    public function down(): void
    {
        Schema::table('athlete_registrations', function (Blueprint $table) {
            $table->dropForeign(['event_group_id']);
            $table->dropIndex('athlete_registrations_group_membership_index');
            $table->dropColumn([
                'event_group_id',
                'group_membership_status',
                'group_membership_role',
            ]);
        });
    }
};

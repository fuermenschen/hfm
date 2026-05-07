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
        Schema::create('donation_event_partner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_event_id')->constrained('donation_events')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['donation_event_id', 'partner_id'], 'dep_event_partner_unique');
            $table->index(['donation_event_id', 'is_published', 'sort_order'], 'dep_event_pub_sort_idx');
        });

        Schema::create('donation_event_sponsor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_event_id')->constrained('donation_events')->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
            $table->string('size')->default('medium');
            $table->text('contribution_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['donation_event_id', 'sponsor_id'], 'des_event_sponsor_unique');
            $table->index(['donation_event_id', 'is_published', 'sort_order'], 'des_event_pub_sort_idx');
        });

        Schema::create('donation_event_faq', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_event_id')->constrained('donation_events')->cascadeOnDelete();
            $table->foreignId('faq_id')->constrained('faqs')->cascadeOnDelete();
            $table->string('group')->default('general');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['donation_event_id', 'faq_id'], 'def_event_faq_unique');
            $table->index(['donation_event_id', 'group', 'is_published', 'sort_order'], 'def_event_group_pub_sort_idx');
        });

        Schema::create('donation_event_sport_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_event_id')->constrained('donation_events')->cascadeOnDelete();
            $table->foreignId('sport_type_id')->constrained('sport_types')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['donation_event_id', 'sport_type_id'], 'dest_event_sport_type_unique');
            $table->index(['donation_event_id', 'is_enabled', 'sort_order'], 'dest_event_enabled_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_event_sport_type');
        Schema::dropIfExists('donation_event_faq');
        Schema::dropIfExists('donation_event_sponsor');
        Schema::dropIfExists('donation_event_partner');
    }
};

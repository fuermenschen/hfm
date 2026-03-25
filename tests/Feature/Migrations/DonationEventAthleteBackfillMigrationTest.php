<?php

use App\Models\Athlete;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

it('backfills athlete donation events and exports deleted out-of-range records with relations', function (): void {
    $createDonationEventsMigration = require database_path('migrations/2026_03_24_151416_create_donation_events_table.php');
    $addDonationEventIdMigration = require database_path('migrations/2026_03_24_151421_add_donation_event_id_to_athletes_table.php');

    if (Schema::hasColumn('athletes', 'donation_event_id')) {
        Schema::table('athletes', function ($table): void {
            $table->dropForeign(['donation_event_id']);
            $table->dropColumn('donation_event_id');
        });
    }

    if (Schema::hasTable('donation_events')) {
        Schema::drop('donation_events');
    }

    $createDonationEventsMigration->up();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::query()->create(['name' => 'Test Partner']);

    $athlete2025 = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'created_at' => '2025-06-01 12:00:00',
        'updated_at' => '2025-06-01 12:00:00',
    ]);

    $athlete2026 = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'created_at' => '2026-06-01 12:00:00',
        'updated_at' => '2026-06-01 12:00:00',
    ]);

    $athlete2024 = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'created_at' => '2024-06-01 12:00:00',
        'updated_at' => '2024-06-01 12:00:00',
    ]);

    $donorForArchivedDonation = Donor::factory()->create();
    $donorForKeptDonation = Donor::factory()->create();

    $archivedDonationId = (int) DB::table('donations')->insertGetId([
        'donor_id' => $donorForArchivedDonation->id,
        'athlete_id' => $athlete2024->id,
        'amount_per_round' => 10,
        'amount_min' => 10,
        'amount_max' => 100,
        'comment' => 'archived',
        'verified' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('donations')->insert([
        'donor_id' => $donorForKeptDonation->id,
        'athlete_id' => $athlete2025->id,
        'amount_per_round' => 12,
        'amount_min' => 12,
        'amount_max' => 120,
        'comment' => 'kept',
        'verified' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('donations')->where('id', $archivedDonationId)->exists())->toBeTrue();

    $filesBefore = Storage::disk('local')->files('exports');

    Log::spy();
    $addDonationEventIdMigration->up();

    $eventIdsBySlug = DB::table('donation_events')->pluck('id', 'slug')->all();

    expect($eventIdsBySlug)->toHaveKeys(['2025', '2026']);
    expect(DB::table('athletes')->where('id', $athlete2025->id)->value('donation_event_id'))->toBe((int) $eventIdsBySlug['2025']);
    expect(DB::table('athletes')->where('id', $athlete2026->id)->value('donation_event_id'))->toBe((int) $eventIdsBySlug['2026']);

    expect(DB::table('athletes')->where('id', $athlete2024->id)->exists())->toBeFalse();
    expect(DB::table('donations')->where('id', $archivedDonationId)->exists())->toBeFalse();
    expect(DB::table('donors')->where('id', $donorForArchivedDonation->id)->exists())->toBeTrue();

    $filesAfter = Storage::disk('local')->files('exports');
    $newExportFiles = array_values(array_diff($filesAfter, $filesBefore));

    expect($newExportFiles)->toHaveCount(1);
    expect($newExportFiles[0])->toStartWith('exports/deleted-unassigned-athletes_');

    $export = json_decode((string) Storage::disk('local')->get($newExportFiles[0]), true);

    expect($export['athlete_ids'])->toContain($athlete2024->id);
    expect(collect($export['athletes'])->pluck('id')->map(fn (mixed $id): int => (int) $id)->all())->toContain($athlete2024->id);
    expect($export)->toHaveKeys(['donations', 'donors']);
    expect($export['donations'])->toBeArray();
    expect($export['donors'])->toBeArray();

    Log::shouldHaveReceived('warning')->once();
});

it('can run the athlete donation event migration down and up repeatedly', function (): void {
    $createDonationEventsMigration = require database_path('migrations/2026_03_24_151416_create_donation_events_table.php');
    $addDonationEventIdMigration = require database_path('migrations/2026_03_24_151421_add_donation_event_id_to_athletes_table.php');

    if (Schema::hasColumn('athletes', 'donation_event_id')) {
        Schema::table('athletes', function ($table): void {
            $table->dropForeign(['donation_event_id']);
            $table->dropColumn('donation_event_id');
        });
    }

    if (Schema::hasTable('donation_events')) {
        Schema::drop('donation_events');
    }

    $createDonationEventsMigration->up();
    $addDonationEventIdMigration->up();

    expect(Schema::hasColumn('athletes', 'donation_event_id'))->toBeTrue();
    expect(Schema::hasColumn('donation_events', 'timezone'))->toBeTrue();
    expect(Schema::hasColumn('donation_events', 'content'))->toBeTrue();
    expect(Schema::hasColumn('donation_events', 'is_published'))->toBeTrue();

    $addDonationEventIdMigration->down();

    expect(Schema::hasColumn('athletes', 'donation_event_id'))->toBeFalse();

    $addDonationEventIdMigration->up();

    expect(Schema::hasColumn('athletes', 'donation_event_id'))->toBeTrue();
});

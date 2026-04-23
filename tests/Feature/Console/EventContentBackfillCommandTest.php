<?php

use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\SportType;
use Database\Seeders\DonationEventSeeder;
use Database\Seeders\EventContentBackfillSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('recommends content-assets in non-interactive mode when asset files are missing in storage', function (): void {
    resetEventContentAssetTargets();

    $this->artisan('hfm:backfill:event-content', [
        '--no-prompt' => true,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Part: content-assets')
        ->assertSuccessful();
});

it('does not recommend content-assets when targets are already present', function (): void {
    resetEventContentAssetTargets();

    $this->seed(DonationEventSeeder::class);
    $this->seed(EventContentBackfillSeeder::class);

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $eventId = (int) DonationEvent::query()->where('slug', '2025')->value('id');
    DB::table('donation_event_sport_type')->insert([
        'donation_event_id' => $eventId,
        'sport_type_id' => $sportType->id,
        'sort_order' => 10,
        'is_enabled' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach (canonicalAssetSourceEntries() as $entry) {
        $sourceAbsolutePath = base_path($entry['source']);
        $targetAbsolutePath = storage_path('app/public/'.$entry['target'].'/'.basename($entry['source']));

        if (! File::exists($sourceAbsolutePath)) {
            continue;
        }

        File::copy($sourceAbsolutePath, $targetAbsolutePath);
    }

    $this->artisan('hfm:backfill:event-content', [
        '--no-prompt' => true,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('No backfill parts selected. Nothing to do.')
        ->doesntExpectOutputToContain('Part: content-assets')
        ->assertSuccessful();
});

it('reports missing source files during content-assets backfill without failing', function (): void {
    resetEventContentAssetTargets();

    Partner::query()->create([
        'name' => 'Missing Asset Partner',
        'logo_light_filename' => 'does-not-exist.svg',
        'logo_dark_filename' => 'bruehlgut_dark.svg',
    ]);

    $this->artisan('hfm:backfill:event-content', [
        '--part' => ['content-assets'],
        '--no-prompt' => true,
    ])
        ->expectsOutputToContain('Missing source file: resources/images/does-not-exist.svg')
        ->expectsOutputToContain('Assets missing at source:')
        ->assertSuccessful();
});

/**
 * @return array<int, array{source: string, target: string}>
 */
function canonicalAssetSourceEntries(): array
{
    return [
        ['source' => 'resources/images/bruehlgut_light.svg', 'target' => 'partners'],
        ['source' => 'resources/images/bruehlgut_dark.svg', 'target' => 'partners'],
        ['source' => 'resources/images/iks_light.svg', 'target' => 'partners'],
        ['source' => 'resources/images/iks_dark.svg', 'target' => 'partners'],
        ['source' => 'resources/images/143_light.svg', 'target' => 'partners'],
        ['source' => 'resources/images/143_dark.svg', 'target' => 'partners'],
        ['source' => 'resources/images/sponsor_logos/rohner_spiller.svg', 'target' => 'sponsors'],
        ['source' => 'resources/images/sponsor_logos/tm_kommunikation.svg', 'target' => 'sponsors'],
        ['source' => 'resources/images/sponsor_logos/veloplus.svg', 'target' => 'sponsors'],
        ['source' => 'resources/images/sponsor_logos/intersport_egli.svg', 'target' => 'sponsors'],
    ];
}

function resetEventContentAssetTargets(): void
{
    File::deleteDirectory(storage_path('app/public/partners'));
    File::deleteDirectory(storage_path('app/public/sponsors'));
    File::ensureDirectoryExists(storage_path('app/public/partners'));
    File::ensureDirectoryExists(storage_path('app/public/sponsors'));
}

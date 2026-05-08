<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DonationEventSeeder;
use Database\Seeders\EventContentFaqsBackfillSeeder;
use Database\Seeders\EventContentPartnersBackfillSeeder;
use Database\Seeders\EventContentSponsorsBackfillSeeder;
use Database\Seeders\EventContentSportTypesBackfillSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class BackfillAthleteEventAssignmentsCommand extends Command
{
    protected $signature = 'hfm:backfill:event-content
        {--part=* : Backfill parts to run}
        {--all : Run all backfill parts}
        {--dry-run : Show changes without writing them}
        {--delete-unresolved : Delete unresolved athletes after exporting}
        {--no-prompt : Do not ask interactive questions}';

    protected $description = 'Run event backfill with selectable parts';

    /**
     * @var array<int, string>
     */
    protected array $availableParts = [
        'events',
        'athlete-assignments',
        'content-assets',
        'content-partners',
        'content-sponsors',
        'content-faqs',
        'content-sport-types',
    ];

    public function handle(): int
    {
        $parts = $this->resolveParts();

        if ($parts === []) {
            $this->components->warn('No backfill parts selected. Nothing to do.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $hasFailures = false;

        foreach ($parts as $part) {
            $partResult = match ($part) {
                'events' => $this->runEventsBackfill($dryRun),
                'athlete-assignments' => $this->runAthleteAssignmentBackfill($dryRun),
                'content-assets' => $this->runContentAssetsBackfill($dryRun),
                'content-partners' => $this->runContentPartnersBackfill($dryRun),
                'content-sponsors' => $this->runContentSponsorsBackfill($dryRun),
                'content-faqs' => $this->runContentFaqsBackfill($dryRun),
                'content-sport-types' => $this->runContentSportTypesBackfill($dryRun),
                default => true,
            };

            if (! $partResult) {
                $hasFailures = true;
            }
        }

        $this->newLine();
        if ($hasFailures) {
            $this->components->error('Event backfill completed with failures.');

            return self::FAILURE;
        }

        $this->components->info('Event backfill completed.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveParts(): array
    {
        if ((bool) $this->option('all')) {
            return $this->availableParts;
        }

        /** @var array<int, string> $parts */
        $parts = array_values(array_unique(array_filter(
            (array) $this->option('part'),
            fn (mixed $part): bool => is_string($part) && in_array($part, $this->availableParts, true),
        )));

        if ($parts !== []) {
            return $parts;
        }

        if ((bool) $this->option('no-prompt') || ! $this->input->isInteractive()) {
            return $this->guessRecommendedParts();
        }

        intro('HFM Event Backfill');
        note('Choose which parts to backfill. Recommended parts are preselected based on current DB state.');

        $recommendedParts = $this->guessRecommendedParts();

        if ($recommendedParts !== []) {
            info('Recommended: '.implode(', ', $recommendedParts));
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'What should be backfilled?',
            options: $this->availableParts,
            default: $recommendedParts,
            required: false,
            hint: 'Leave empty to cancel.',
        );

        if ($selected === [] && confirm('No part selected. Run recommended parts instead?', default: true)) {
            return $recommendedParts;
        }

        return $selected;
    }

    /**
     * @return array<int, string>
     */
    protected function guessRecommendedParts(): array
    {
        $recommended = [];

        $eventCount = (int) DB::table('donation_events')->whereIn('slug', ['2025', '2026'])->count();
        if ($eventCount < 2) {
            $recommended[] = 'events';
        }

        $unassignedAthleteCount = (int) DB::table('athletes')->whereNull('donation_event_id')->count();
        if ($unassignedAthleteCount > 0) {
            $recommended[] = 'athlete-assignments';
        }

        if ($this->hasPendingAssetCopies()) {
            $recommended[] = 'content-assets';
        }

        $eventPartnerCount = (int) DB::table('donation_event_partner')->count();
        if ($eventPartnerCount === 0) {
            $recommended[] = 'content-partners';
        }

        $eventSponsorCount = (int) DB::table('donation_event_sponsor')->count();
        if ($eventSponsorCount === 0) {
            $recommended[] = 'content-sponsors';
        }

        $eventFaqCount = (int) DB::table('donation_event_faq')->count();
        if ($eventFaqCount === 0) {
            $recommended[] = 'content-faqs';
        }

        $eventSportTypeCount = (int) DB::table('donation_event_sport_type')->count();
        if ($eventSportTypeCount === 0) {
            $recommended[] = 'content-sport-types';
        }

        return array_values(array_unique($recommended));
    }

    protected function runEventsBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: events');

        if ($dryRun) {
            warning('Dry run: DonationEventSeeder would be executed.');

            return true;
        }

        $this->call('db:seed', ['--class' => DonationEventSeeder::class, '--force' => true]);

        return true;
    }

    protected function runContentAssetsBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: content-assets');

        $uniqueEntries = $this->assetSourceEntries();

        File::ensureDirectoryExists(storage_path('app/public/partners'));
        File::ensureDirectoryExists(storage_path('app/public/sponsors'));

        $copied = 0;
        $skippedExisting = 0;
        $missingSource = 0;

        foreach ($uniqueEntries as $entry) {
            $sourceRelativePath = $entry['source'];
            $targetDirectory = $entry['target'];
            $sourceAbsolutePath = base_path($sourceRelativePath);
            $filename = basename($sourceRelativePath);
            $targetAbsolutePath = storage_path('app/public/'.$targetDirectory.'/'.$filename);

            if (! File::exists($sourceAbsolutePath)) {
                $missingSource++;
                $this->warn('Missing source file: '.$sourceRelativePath);

                continue;
            }

            if (File::exists($targetAbsolutePath)) {
                $skippedExisting++;

                continue;
            }

            if ($dryRun) {
                $copied++;

                continue;
            }

            File::copy($sourceAbsolutePath, $targetAbsolutePath);
            $copied++;
        }

        if ($dryRun) {
            warning('Dry run: no files were copied.');
        }

        $this->line('Assets copied: '.$copied);
        $this->line('Assets skipped (already existing): '.$skippedExisting);
        $this->line('Assets missing at source: '.$missingSource);

        return true;
    }

    protected function hasPendingAssetCopies(): bool
    {
        foreach ($this->assetSourceEntries() as $entry) {
            $sourceAbsolutePath = base_path($entry['source']);
            $targetAbsolutePath = storage_path('app/public/'.$entry['target'].'/'.basename($entry['source']));

            if (! File::exists($sourceAbsolutePath)) {
                continue;
            }

            if (! File::exists($targetAbsolutePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{source: string, target: string}>
     */
    protected function assetSourceEntries(): array
    {
        /** @var array<int, array{source: string, target: string}> $sourceEntries */
        $sourceEntries = $this->canonicalAssetSourcePaths();

        if (Schema::hasTable('partners')) {
            $partnerEntries = DB::table('partners')
                ->whereNotNull('logo_light_filename')
                ->pluck('logo_light_filename')
                ->merge(DB::table('partners')->whereNotNull('logo_dark_filename')->pluck('logo_dark_filename'))
                ->filter(fn (mixed $f): bool => is_string($f) && $f !== '')
                ->map(fn (string $filename): array => ['source' => 'resources/images/'.$filename, 'target' => 'partners'])
                ->values()
                ->all();

            $sourceEntries = array_merge($sourceEntries, $partnerEntries);
        }

        if (Schema::hasTable('sponsors')) {
            $sponsorEntries = DB::table('sponsors')
                ->whereNotNull('logo_filename')
                ->pluck('logo_filename')
                ->filter(fn (mixed $f): bool => is_string($f) && $f !== '')
                ->map(fn (string $filename): array => ['source' => 'resources/images/sponsor_logos/'.$filename, 'target' => 'sponsors'])
                ->values()
                ->all();

            $sourceEntries = array_merge($sourceEntries, $sponsorEntries);
        }

        return $this->deduplicateAssetSourceEntries($sourceEntries);
    }

    /**
     * @param  array<int, array{source: string, target: string}>  $sourceEntries
     * @return array<int, array{source: string, target: string}>
     */
    protected function deduplicateAssetSourceEntries(array $sourceEntries): array
    {
        $seen = [];
        $uniqueEntries = [];
        foreach ($sourceEntries as $entry) {
            $key = $entry['target'].'|'.$entry['source'];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueEntries[] = $entry;
            }
        }

        return $uniqueEntries;
    }

    /**
     * @return array<int, array{source: string, target: string}>
     */
    protected function canonicalAssetSourcePaths(): array
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

    protected function runContentPartnersBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: content-partners');

        if ($dryRun) {
            warning('Dry run: EventContentPartnersBackfillSeeder would be executed.');

            return true;
        }

        $this->call('db:seed', ['--class' => EventContentPartnersBackfillSeeder::class, '--force' => true]);

        return true;
    }

    protected function runContentSponsorsBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: content-sponsors');

        if ($dryRun) {
            warning('Dry run: EventContentSponsorsBackfillSeeder would be executed.');

            return true;
        }

        $this->call('db:seed', ['--class' => EventContentSponsorsBackfillSeeder::class, '--force' => true]);

        return true;
    }

    protected function runContentFaqsBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: content-faqs');

        if ($dryRun) {
            warning('Dry run: EventContentFaqsBackfillSeeder would be executed.');

            return true;
        }

        $this->call('db:seed', ['--class' => EventContentFaqsBackfillSeeder::class, '--force' => true]);

        return true;
    }

    protected function runContentSportTypesBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: content-sport-types');

        if ($dryRun) {
            warning('Dry run: EventContentSportTypesBackfillSeeder would be executed.');

            return true;
        }

        $this->call('db:seed', ['--class' => EventContentSportTypesBackfillSeeder::class, '--force' => true]);

        return true;
    }

    protected function runAthleteAssignmentBackfill(bool $dryRun): bool
    {
        $this->newLine();
        $this->components->info('Part: athlete-assignments');

        $deleteUnresolved = (bool) $this->option('delete-unresolved');

        if ($deleteUnresolved) {
            warning('delete-unresolved is enabled. Unresolved athletes will be deleted after export.');
        }

        $eventIds = DB::table('donation_events')
            ->whereIn('slug', ['2025', '2026'])
            ->pluck('id', 'slug')
            ->all();

        $event2025Id = (int) ($eventIds['2025'] ?? 0);
        $event2026Id = (int) ($eventIds['2026'] ?? 0);

        if ($event2025Id <= 0 || $event2026Id <= 0) {
            $this->components->error('Canonical donation events (2025/2026) are missing. Run the "events" part first.');

            return false;
        }

        $assignable2025 = DB::table('athletes')
            ->whereNull('donation_event_id')
            ->where('created_at', '>=', '2025-01-01 00:00:00')
            ->where('created_at', '<', '2026-01-01 00:00:00');

        $assignable2026 = DB::table('athletes')
            ->whereNull('donation_event_id')
            ->where('created_at', '>=', '2026-01-01 00:00:00')
            ->where('created_at', '<', '2027-01-01 00:00:00');

        $assignable2025Count = (clone $assignable2025)->count();
        $assignable2026Count = (clone $assignable2026)->count();

        if (! $dryRun) {
            $assignable2025->update(['donation_event_id' => $event2025Id]);
            $assignable2026->update(['donation_event_id' => $event2026Id]);
        }

        $unresolvedAthleteIds = DB::table('athletes')
            ->whereNull('donation_event_id')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $exportPath = $this->exportUnresolvedAthletes($unresolvedAthleteIds, $dryRun);

        if ($deleteUnresolved && ! $dryRun && $unresolvedAthleteIds !== []) {
            DB::table('athletes')->whereIn('id', $unresolvedAthleteIds)->delete();
        }

        $this->components->info('Athlete event assignment backfill completed.');
        $this->line('Assigned to 2025: '.$assignable2025Count);
        $this->line('Assigned to 2026: '.$assignable2026Count);
        $this->line('Unresolved athletes: '.count($unresolvedAthleteIds));

        if ($exportPath !== null) {
            $this->line('Unresolved export: '.$exportPath);
        }

        if ($dryRun) {
            $this->components->warn('Dry run mode: no records were modified.');
        }

        return true;
    }

    /**
     * @param  array<int, int>  $unresolvedAthleteIds
     */
    protected function exportUnresolvedAthletes(array $unresolvedAthleteIds, bool $dryRun): ?string
    {
        if ($unresolvedAthleteIds === []) {
            return null;
        }

        $athletes = DB::table('athletes')
            ->whereIn('id', $unresolvedAthleteIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $donations = DB::table('donations')
            ->whereIn('athlete_id', $unresolvedAthleteIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $donorIds = array_values(array_unique(array_map(
            static fn (array $donation): int => (int) $donation['donor_id'],
            $donations,
        )));

        $donors = $donorIds === []
            ? []
            : DB::table('donors')
                ->whereIn('id', $donorIds)
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();

        $timestamp = now()->format('Ymd_His');
        $relativePath = sprintf('exports/unresolved-athlete-event-assignments_%s.json', $timestamp);
        $absolutePath = storage_path('app/'.$relativePath);

        if (! $dryRun) {
            Storage::disk('local')->makeDirectory('exports');

            Storage::disk('local')->put(
                $relativePath,
                json_encode([
                    'generated_at' => now()->toIso8601String(),
                    'reason' => 'Athletes could not be assigned to canonical event windows (2025/2026).',
                    'athlete_ids' => $unresolvedAthleteIds,
                    'athletes' => $athletes,
                    'donations' => $donations,
                    'donors' => $donors,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }

        return $absolutePath;
    }
}

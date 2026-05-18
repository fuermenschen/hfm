<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Athlete;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\ExternalUser;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillExternalUsersCommand extends Command
{
    protected $signature = 'hfm:backfill:external-users
        {--dry-run : Show preflight results without writing}';

    protected $description = 'Backfill external users with strict preflight safety checks';

    private const TARGET_SCHEMA_NOT_EMPTY_MESSAGE = 'Target tables/columns are not empty.';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->components->info('External users backfill preflight');

        $preflight = $this->runPreflightChecks();

        foreach ($preflight['details'] as $label => $count) {
            $this->line($label.': '.$count);
        }

        if ($preflight['blockers'] !== []) {
            $this->newLine();
            $this->components->error('Preflight failed. Fix blockers before running write mode.');

            foreach ($preflight['blockers'] as $blocker) {
                $this->line('- '.$blocker);
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Preflight passed. Safe to write.');

        $plan = $this->buildPlan();

        $this->newLine();
        $this->line('identity rows to create: '.$plan['external_user_rows']->count());
        $this->line('athlete registrations to create: '.$plan['athlete_registration_rows']->count());
        $this->line('donations to update: '.$plan['donation_updates']->count());

        if ($isDryRun) {
            $this->line('Dry run: write phase not executed.');
            $this->printDryRunMergePreview($plan['merge_preview']);

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan): void {
            $this->executeWritePlan($plan);
            $this->validatePostWriteParity();
        });

        $this->components->info('Backfill write completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array{details: array<string, int>, blockers: array<int, string>}
     */
    protected function runPreflightChecks(): array
    {
        $details = $this->collectPreflightDetails();
        $blockers = $this->collectPreflightBlockers($details);

        return [
            'details' => $details,
            'blockers' => $blockers,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function collectPreflightDetails(): array
    {
        return [
            'duplicate normalized donor emails' => $this->countDuplicateNormalizedDonorEmails(),
            'duplicate normalized athlete emails' => $this->countDuplicateNormalizedAthleteEmails(),
            'donors with null email' => (int) DB::table('donors')->whereNull('email')->count(),
            'athletes with null email' => (int) DB::table('athletes')->whereNull('email')->count(),
            'donors with blank normalized email' => $this->countDonorsWithBlankNormalizedEmail(),
            'athletes with blank normalized email' => $this->countAthletesWithBlankNormalizedEmail(),
            'duplicate donor/athlete donation pairs' => $this->countDuplicateDonationPairs(),
            'athletes without donation_event_id' => (int) DB::table('athletes')->whereNull('donation_event_id')->count(),
            'donations with missing donor relation' => $this->countDonationsMissingDonor(),
            'donations with missing athlete relation' => $this->countDonationsMissingAthlete(),
            'external_users rows' => (int) DB::table('external_users')->count(),
            'athlete_registrations rows' => (int) DB::table('athlete_registrations')->count(),
            'donations with donor_external_user_id' => (int) DB::table('donations')->whereNotNull('donor_external_user_id')->count(),
            'donations with athlete_registration_id' => (int) DB::table('donations')->whereNotNull('athlete_registration_id')->count(),
        ];
    }

    /**
     * @param  array<string, int>  $details
     * @return array<int, string>
     */
    protected function collectPreflightBlockers(array $details): array
    {
        $blockers = [];

        if ($details['duplicate normalized donor emails'] > 0) {
            $blockers[] = 'Duplicate normalized donor emails found.';
        }

        if ($details['duplicate normalized athlete emails'] > 0) {
            $blockers[] = 'Duplicate normalized athlete emails found.';
        }

        if ($details['donors with null email'] > 0) {
            $blockers[] = 'Donors with null emails found.';
        }

        if ($details['athletes with null email'] > 0) {
            $blockers[] = 'Athletes with null emails found.';
        }

        if ($details['donors with blank normalized email'] > 0) {
            $blockers[] = 'Donors with blank normalized emails found.';
        }

        if ($details['athletes with blank normalized email'] > 0) {
            $blockers[] = 'Athletes with blank normalized emails found.';
        }

        if ($details['duplicate donor/athlete donation pairs'] > 0) {
            $blockers[] = 'Duplicate legacy donation pairs (donor_id, athlete_id) found.';
        }

        if ($details['athletes without donation_event_id'] > 0) {
            $blockers[] = 'Athletes without donation_event_id found.';
        }

        if ($details['donations with missing donor relation'] > 0 || $details['donations with missing athlete relation'] > 0) {
            $blockers[] = 'Donations with missing legacy donor or athlete relations found.';
        }

        if ($this->targetSchemaContainsData($details)) {
            $blockers[] = self::TARGET_SCHEMA_NOT_EMPTY_MESSAGE;
        }

        return $blockers;
    }

    /**
     * @param  array<string, int>  $details
     */
    protected function targetSchemaContainsData(array $details): bool
    {
        return $details['external_users rows'] > 0
            || $details['athlete_registrations rows'] > 0
            || $details['donations with donor_external_user_id'] > 0
            || $details['donations with athlete_registration_id'] > 0;
    }

    protected function countDuplicateNormalizedDonorEmails(): int
    {
        $normalizedCounts = DB::table('donors')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(function (mixed $email): string {
                if (! is_string($email)) {
                    return '';
                }

                return trim(mb_strtolower($email));
            })
            ->filter(fn (string $email): bool => $email !== '')
            ->countBy();

        return $normalizedCounts->filter(fn (int $count): bool => $count > 1)->count();
    }

    protected function countDuplicateNormalizedAthleteEmails(): int
    {
        $normalizedCounts = DB::table('athletes')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(function (mixed $email): string {
                if (! is_string($email)) {
                    return '';
                }

                return trim(mb_strtolower($email));
            })
            ->filter(fn (string $email): bool => $email !== '')
            ->countBy();

        return $normalizedCounts->filter(fn (int $count): bool => $count > 1)->count();
    }

    protected function countDuplicateDonationPairs(): int
    {
        return (int) DB::table('donations')
            ->select('donor_id', 'athlete_id')
            ->groupBy('donor_id', 'athlete_id')
            ->havingRaw('count(*) > 1')
            ->count();
    }

    protected function countDonorsWithBlankNormalizedEmail(): int
    {
        return (int) DB::table('donors')
            ->whereRaw('TRIM(email) = ?', [''])
            ->count();
    }

    protected function countAthletesWithBlankNormalizedEmail(): int
    {
        return (int) DB::table('athletes')
            ->whereRaw('TRIM(email) = ?', [''])
            ->count();
    }

    protected function countDonationsMissingDonor(): int
    {
        return (int) DB::table('donations')
            ->leftJoin('donors', 'donations.donor_id', '=', 'donors.id')
            ->whereNull('donors.id')
            ->count();
    }

    protected function countDonationsMissingAthlete(): int
    {
        return (int) DB::table('donations')
            ->leftJoin('athletes', 'donations.athlete_id', '=', 'athletes.id')
            ->whereNull('athletes.id')
            ->count();
    }

    /**
     * @param  array{
     *     external_user_rows: Collection<int, array<string, mixed>>,
     *     athlete_registration_rows: Collection<int, array<string, mixed>>,
     *     donation_updates: Collection<int, array{donation_id: int, donor_id: int, athlete_id: int}>,
     *     merge_preview: Collection<int, string>
     * }  $plan
     */
    protected function executeWritePlan(array $plan): void
    {
        ExternalUser::query()->insert($plan['external_user_rows']->all());

        $externalUsersByLegacyAthleteId = ExternalUser::query()
            ->whereNotNull('legacy_athlete_id')
            ->pluck('id', 'legacy_athlete_id');

        AthleteRegistration::query()->insert(
            $plan['athlete_registration_rows']->map(function (array $row) use ($externalUsersByLegacyAthleteId): array {
                $row['external_user_id'] = $externalUsersByLegacyAthleteId[(int) $row['legacy_athlete_id']];
                unset($row['legacy_athlete_id']);

                return $row;
            })->all()
        );

        $externalUsersByLegacyDonorId = ExternalUser::query()
            ->whereNotNull('legacy_donor_id')
            ->pluck('id', 'legacy_donor_id');

        $athleteRegistrationsByAthleteId = AthleteRegistration::query()
            ->join('external_users', 'external_users.id', '=', 'athlete_registrations.external_user_id')
            ->pluck('athlete_registrations.id', 'external_users.legacy_athlete_id');

        foreach ($plan['donation_updates'] as $update) {
            Donation::query()
                ->whereKey((int) $update['donation_id'])
                ->update([
                    'donor_external_user_id' => $externalUsersByLegacyDonorId[(int) $update['donor_id']],
                    'athlete_registration_id' => $athleteRegistrationsByAthleteId[(int) $update['athlete_id']],
                ]);
        }
    }

    /**
     * @return array{
     *     external_user_rows: Collection<int, array<string, mixed>>,
     *     athlete_registration_rows: Collection<int, array<string, mixed>>,
     *     donation_updates: Collection<int, array{donation_id: int, donor_id: int, athlete_id: int}>,
     *     merge_preview: Collection<int, string>
     * }
     */
    protected function buildPlan(): array
    {
        $now = now();
        $reservedPublicIds = DB::table('external_users')->pluck('public_id')->filter()->values()->all();

        $athletes = Athlete::query()->orderBy('id')->get();
        $donors = Donor::query()->orderBy('id')->get();
        $donations = Donation::query()->orderBy('id')->get();

        /** @var Collection<string, array{athlete: Athlete|null, donor: Donor|null}> $identityMap */
        $identityMap = collect();

        foreach ($athletes as $athlete) {
            $normalizedEmail = $this->normalizeEmail($athlete->email);
            $existing = $identityMap->get($normalizedEmail, ['athlete' => null, 'donor' => null]);
            $existing['athlete'] = $athlete;
            $identityMap->put($normalizedEmail, $existing);
        }

        foreach ($donors as $donor) {
            $normalizedEmail = $this->normalizeEmail($donor->email);
            $existing = $identityMap->get($normalizedEmail, ['athlete' => null, 'donor' => null]);
            $existing['donor'] = $donor;
            $identityMap->put($normalizedEmail, $existing);
        }

        $externalUserRows = collect();
        $mergePreview = collect();

        foreach ($identityMap as $email => $identity) {
            $athlete = $identity['athlete'];
            $donor = $identity['donor'];

            $externalUserRows->push([
                'uuid' => (string) str()->uuid(),
                'public_id' => $this->generatePublicId($reservedPublicIds),
                'first_name' => $this->chooseField($athlete?->first_name, $donor?->first_name),
                'last_name' => $this->chooseField($athlete?->last_name, $donor?->last_name),
                'address' => $this->chooseField($athlete?->address, $donor?->address),
                'zip_code' => $this->chooseField($athlete?->zip_code !== null ? (string) $athlete->zip_code : null, $donor?->zip_code),
                'city' => $this->chooseField($athlete?->city, $donor?->city),
                'country_of_residence' => $this->resolveCountryOfResidence($donor?->country_of_residence),
                'phone_number' => $this->chooseField($athlete?->phone_number, $donor?->phone_number),
                'email' => $email,
                'legacy_athlete_id' => $athlete?->id,
                'legacy_donor_id' => $donor?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $mergePreview->push(sprintf(
                '%s => athlete:%s donor:%s',
                $email,
                $athlete?->id !== null ? (string) $athlete->id : '-',
                $donor?->id !== null ? (string) $donor->id : '-'
            ));
        }

        $athleteRegistrationRows = $athletes->map(function (Athlete $athlete) use ($now): array {
            return [
                'legacy_athlete_id' => $athlete->id,
                'donation_event_id' => $athlete->donation_event_id,
                'sport_type_id' => $athlete->sport_type_id,
                'partner_id' => $athlete->partner_id,
                'rounds_estimated' => $athlete->rounds_estimated,
                'rounds_done' => $athlete->rounds_done,
                'comment' => $athlete->comment,
                'verified' => $athlete->verified,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        $donationUpdates = $donations->map(function (Donation $donation): array {
            return [
                'donation_id' => $donation->id,
                'donor_id' => (int) $donation->donor_id,
                'athlete_id' => (int) $donation->athlete_id,
            ];
        });

        return [
            'external_user_rows' => $externalUserRows,
            'athlete_registration_rows' => $athleteRegistrationRows,
            'donation_updates' => $donationUpdates,
            'merge_preview' => $mergePreview,
        ];
    }

    protected function normalizeEmail(?string $email): string
    {
        return trim(mb_strtolower((string) $email));
    }

    /**
     * @param  array<int, string>  $reservedPublicIds
     */
    protected function generatePublicId(array &$reservedPublicIds): string
    {
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $id = '';

            for ($index = 0; $index < 6; $index++) {
                $id .= $charset[random_int(0, strlen($charset) - 1)];
            }
        } while (in_array($id, $reservedPublicIds, true));

        $reservedPublicIds[] = $id;

        return $id;
    }

    protected function chooseField(mixed $athleteValue, mixed $donorValue): ?string
    {
        $athleteString = is_string($athleteValue) ? trim($athleteValue) : '';
        if ($athleteString !== '') {
            return $athleteString;
        }

        $donorString = is_string($donorValue) ? trim($donorValue) : '';
        if ($donorString !== '') {
            return $donorString;
        }

        return null;
    }

    protected function resolveCountryOfResidence(?string $countryOfResidence): string
    {
        $normalizedCountry = trim((string) $countryOfResidence);

        if ($normalizedCountry === '') {
            return 'CH';
        }

        return $normalizedCountry;
    }

    protected function printDryRunMergePreview(Collection $mergePreview): void
    {
        $this->newLine();
        $this->line('Dry-run merge preview:');

        foreach ($mergePreview->take(25) as $line) {
            $this->line('- '.$line);
        }

        if ($mergePreview->count() > 25) {
            $this->line('- ... and '.($mergePreview->count() - 25).' more');
        }
    }

    protected function validatePostWriteParity(): void
    {
        $legacyAthleteCount = (int) Athlete::query()->count();
        $newAthleteRegistrationCount = (int) AthleteRegistration::query()->count();

        if ($legacyAthleteCount !== $newAthleteRegistrationCount) {
            $this->fail('Parity check failed: athlete registrations count mismatch.');
        }

        $legacyDonationCount = (int) Donation::query()->count();
        $newDonationMappedCount = (int) Donation::query()
            ->whereNotNull('donor_external_user_id')
            ->whereNotNull('athlete_registration_id')
            ->count();

        if ($legacyDonationCount !== $newDonationMappedCount) {
            $this->fail('Parity check failed: donations mapping count mismatch.');
        }

        $legacyAthleteMappedCount = (int) ExternalUser::query()->whereNotNull('legacy_athlete_id')->count();
        if ($legacyAthleteMappedCount !== $legacyAthleteCount) {
            $this->fail('Parity check failed: legacy athlete mapping mismatch.');
        }

        $legacyDonorCount = (int) Donor::query()->count();
        $legacyDonorMappedCount = (int) ExternalUser::query()->whereNotNull('legacy_donor_id')->count();
        if ($legacyDonorMappedCount !== $legacyDonorCount) {
            $this->fail('Parity check failed: legacy donor mapping mismatch.');
        }

        $legacyRows = DB::table('donations')
            ->join('athletes', 'athletes.id', '=', 'donations.athlete_id')
            ->selectRaw('athletes.donation_event_id as event_id, donations.donor_id as donor_id, SUM(donations.amount_per_round) as total')
            ->groupBy('athletes.donation_event_id', 'donations.donor_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [sprintf('%d|%d', (int) $row->event_id, (int) $row->donor_id) => (float) $row->total]);

        $newRows = DB::table('donations')
            ->join('athlete_registrations', 'athlete_registrations.id', '=', 'donations.athlete_registration_id')
            ->join('external_users', 'external_users.id', '=', 'donations.donor_external_user_id')
            ->selectRaw('athlete_registrations.donation_event_id as event_id, external_users.legacy_donor_id as donor_id, SUM(donations.amount_per_round) as total')
            ->groupBy('athlete_registrations.donation_event_id', 'external_users.legacy_donor_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [sprintf('%d|%d', (int) $row->event_id, (int) $row->donor_id) => (float) $row->total]);

        if ($legacyRows->count() !== $newRows->count()) {
            $this->fail('Parity check failed: grouped donation totals row count mismatch.');
        }

        foreach ($legacyRows as $key => $legacyTotal) {
            $newTotal = $newRows->get($key);

            if ($newTotal === null || abs($legacyTotal - $newTotal) > 0.00001) {
                $this->fail('Parity check failed: grouped donation totals mismatch for '.$key.'.');
            }
        }
    }
}

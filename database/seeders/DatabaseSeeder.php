<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use App\Settings\EventSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdminUsers();
        $this->seedSportTypes();

        $pastEvent = DonationEvent::factory()->defaults()->withSportTypes()->year(2025)->create();
        $futureEvent = DonationEvent::factory()->defaults()->withSportTypes()->year(2026)->create();

        Partner::factory()->count(6)->create();

        $eventSettings = resolve(EventSettings::class);
        $eventSettings->current_event_id = $futureEvent->id;
        $eventSettings->save();

        if (config('app.env') === 'local') {
            $this->seedLocalScenario($pastEvent, $futureEvent);
        }
    }

    protected function seedAdminUsers(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@hfm.test'],
            User::factory()->localAdmin()->make()->toArray(),
        );
    }

    protected function seedSportTypes(): void
    {
        foreach (['Rennen', 'Velofahren', 'Inlineskaten', 'Rollstuhl', 'Andere (bitte spezifizieren)'] as $name) {
            SportType::query()->firstOrCreate(['name' => $name]);
        }
    }

    protected function seedLocalScenario(DonationEvent $pastEvent, DonationEvent $futureEvent): void
    {
        $donorOnlyUsers = ExternalUser::factory()->count(70)->create();
        $athleteOnlyUsers = ExternalUser::factory()->count(20)->create();
        $dualRoleUsers = ExternalUser::factory()->count(10)->create();

        $athletes2025 = $athleteOnlyUsers->random(7)->merge($dualRoleUsers->random(3));
        $athletes2026 = $athleteOnlyUsers->diff($athletes2025)->values()->merge($dualRoleUsers);

        $registrations2025 = $this->createEventRegistrations($athletes2025, $pastEvent);
        $registrations2026 = $this->createEventRegistrations($athletes2026, $futureEvent);

        $donorPool = $donorOnlyUsers->merge($dualRoleUsers)->values();

        $this->createDonationsForEvent($registrations2025, $donorPool, 70);
        $this->createDonationsForEvent($registrations2026, $donorPool, 150);
    }

    protected function createEventRegistrations(Collection $externalUsers, DonationEvent $event): Collection
    {
        return $externalUsers->map(fn (ExternalUser $externalUser): AthleteRegistration => AthleteRegistration::factory()
            ->forVerifiedEventUser($event, $externalUser)
            ->create());
    }

    /**
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @param  Collection<int, ExternalUser>  $donorPool
     */
    protected function createDonationsForEvent(Collection $registrations, Collection $donorPool, int $count): void
    {
        $pairPool = $this->buildPairPool($registrations, $donorPool)->shuffle()->take($count);

        $pairPool->each(function (array $pair): void {
            Donation::factory()->forPair($pair['donor'], $pair['registration'])->create();
        });
    }

    /**
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @param  Collection<int, ExternalUser>  $donorPool
     * @return Collection<int, array{donor: ExternalUser, registration: AthleteRegistration}>
     */
    protected function buildPairPool(Collection $registrations, Collection $donorPool): Collection
    {
        return $donorPool
            ->flatMap(fn (ExternalUser $donor): Collection => $registrations
                ->map(fn (AthleteRegistration $registration): array => [
                    'donor' => $donor,
                    'registration' => $registration,
                ]))
            ->values();
    }
}

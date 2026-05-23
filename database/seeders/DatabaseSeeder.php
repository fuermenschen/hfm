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

        $pastEvent = DonationEvent::factory()->defaults()->year(2025)->create();
        $futureEvent = DonationEvent::factory()->defaults()->year(2026)->create();

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
        User::query()->firstOrCreate(['email' => 'simon.moser@mailbox.org'], ['name' => 'Simon']);
        User::query()->firstOrCreate(['email' => 'kaifrehner@gmail.com'], ['name' => 'Kai']);
        User::query()->firstOrCreate(['email' => 'felix.moser@mailbox.org'], ['name' => 'Felix']);
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

        $registrations2025 = $athletes2025->map(fn (ExternalUser $externalUser): AthleteRegistration => AthleteRegistration::factory()
            ->forExternalUser($externalUser)
            ->forEvent($pastEvent)
            ->withPartner()
            ->verified()
            ->create());

        $registrations2026 = $athletes2026->map(fn (ExternalUser $externalUser): AthleteRegistration => AthleteRegistration::factory()
            ->forExternalUser($externalUser)
            ->forEvent($futureEvent)
            ->withPartner()
            ->verified()
            ->create());

        $donorPool = $donorOnlyUsers->merge($dualRoleUsers)->values();

        $this->createDonationsForEvent($registrations2025, $donorPool, 70);
        $this->createDonationsForEvent($registrations2026, $donorPool, 150);
    }

    protected function createDonationsForEvent(Collection $registrations, Collection $donorPool, int $count): void
    {
        $registrationIds = $registrations->pluck('id')->values();
        $donorIds = $donorPool->pluck('id')->values();
        $usedPairs = [];

        while (count($usedPairs) < $count) {
            $registrationId = (int) $registrationIds->random();
            $donorId = (int) $donorIds->random();
            $pairKey = $donorId.'-'.$registrationId;

            if (isset($usedPairs[$pairKey])) {
                continue;
            }

            $usedPairs[$pairKey] = true;

            Donation::factory()
                ->forDonorExternalUser($donorId)
                ->forAthleteRegistration($registrationId)
                ->create();
        }
    }
}

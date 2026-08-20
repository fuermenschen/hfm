<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Models\SportType;
use App\Models\User;
use App\Settings\EventSettings;
use App\Settings\InvoiceSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdminUsers();
        $this->seedSportTypes();
        $this->call(DonationEventSeeder::class);

        $pastEvent = DonationEvent::query()->where('slug', '2025')->firstOrFail();
        $futureEvent = DonationEvent::query()->where('slug', '2026')->firstOrFail();
        $this->seedEventAssets();
        $this->seedEventContent(collect([$pastEvent, $futureEvent]));

        $eventSettings = resolve(EventSettings::class);
        $eventSettings->current_event_id = $futureEvent->id;
        $eventSettings->save();
        $this->seedOfficialAddressSettings();

        if (in_array(config('app.env'), ['local', 'testing'], true)) {
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

    protected function seedOfficialAddressSettings(): void
    {
        $settings = resolve(InvoiceSettings::class);

        $settings->creditor_name = $settings->creditor_name !== '' ? $settings->creditor_name : 'Verein für Menschen';
        $settings->creditor_care_of = $settings->creditor_care_of !== '' ? $settings->creditor_care_of : 'Kai Frehner';
        $settings->creditor_street = $settings->creditor_street !== '' ? $settings->creditor_street : 'Rössligasse';
        $settings->creditor_building_number = $settings->creditor_building_number !== '' ? $settings->creditor_building_number : '6';
        $settings->creditor_postal_code = $settings->creditor_postal_code !== '' ? $settings->creditor_postal_code : '8400';
        $settings->creditor_city = $settings->creditor_city !== '' ? $settings->creditor_city : 'Winterthur';
        $settings->save();
    }

    protected function seedEventAssets(): void
    {
        $disk = Storage::disk('public');

        foreach ([
            'partners/bruehlgut_light.svg' => 'images/bruehlgut_light.svg',
            'partners/bruehlgut_dark.svg' => 'images/bruehlgut_dark.svg',
            'partners/iks_light.svg' => 'images/iks_light.svg',
            'partners/iks_dark.svg' => 'images/iks_dark.svg',
            'partners/143_light.svg' => 'images/143_light.svg',
            'partners/143_dark.svg' => 'images/143_dark.svg',
            'partners/vbk_light.svg' => 'images/vbk_light.svg',
            'partners/vbk_dark.svg' => 'images/vbk_dark.svg',
            'partners/windlicht_light.svg' => 'images/windlicht_light.svg',
            'partners/windlicht_dark.svg' => 'images/windlicht_dark.svg',
            'sponsors/rohner_spiller.svg' => 'images/sponsor_logos/rohner_spiller.svg',
            'sponsors/tm_kommunikation.svg' => 'images/sponsor_logos/tm_kommunikation.svg',
            'sponsors/veloplus.svg' => 'images/sponsor_logos/veloplus.svg',
            'sponsors/intersport_egli.svg' => 'images/sponsor_logos/intersport_egli.svg',
        ] as $target => $source) {
            throw_unless(
                $disk->put($target, File::get(resource_path($source))),
                \RuntimeException::class,
                sprintf('Failed to seed event asset [%s].', $target),
            );
        }
    }

    /** @param Collection<int, DonationEvent> $events */
    protected function seedEventContent(Collection $events): void
    {
        $partners = collect([
            [
                'name' => 'Brühlgut Stiftung',
                'logo_light_filename' => 'bruehlgut_light.svg',
                'logo_dark_filename' => 'bruehlgut_dark.svg',
                'beneficiary_blurb' => 'Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung.',
                'url' => 'https://www.bruehlgut.ch/',
            ],
            [
                'name' => 'Institut Kinderseele Schweiz',
                'logo_light_filename' => 'iks_light.svg',
                'logo_dark_filename' => 'iks_dark.svg',
                'beneficiary_blurb' => 'Das Institut Kinderseele Schweiz unterstützt Kinder psychisch erkrankter Eltern.',
                'url' => 'https://www.kinderseele.ch/',
            ],
            [
                'name' => 'Tel. 143 - Die Dargebotene Hand',
                'logo_light_filename' => '143_light.svg',
                'logo_dark_filename' => '143_dark.svg',
                'beneficiary_blurb' => 'Die Dargebotene Hand bietet Menschen in schwierigen Lebenslagen Unterstützung.',
                'url' => 'https://www.143.ch/',
            ],
            [
                'name' => 'Vereinigung Begleitung Kranker',
                'logo_light_filename' => 'vbk_light.svg',
                'logo_dark_filename' => 'vbk_dark.svg',
                'beneficiary_blurb' => 'Die VBK schenkt kranken und sterbenden Menschen Zeit, Nähe und persönliche Begleitung durch geschulte Freiwillige und entlastet dabei ihre Angehörigen.',
                'url' => 'https://begleitung-kranker.ch/',
            ],
            [
                'name' => 'Stiftung Windlicht',
                'logo_light_filename' => 'windlicht_light.svg',
                'logo_dark_filename' => 'windlicht_dark.svg',
                'beneficiary_blurb' => 'Die Stiftung Windlicht stärkt Kinder psychisch erkrankter Eltern, indem sie ihnen Halt, Gemeinschaft und eine Auszeit vom belastenden Familienalltag bietet.',
                'url' => 'https://www.stiftung-windlicht.ch',
            ],
        ])->map(fn (array $attributes): Partner => Partner::query()->updateOrCreate(
            ['name' => $attributes['name']],
            $attributes,
        ));

        $sponsors = collect([
            ['name' => 'Rohner Spiller', 'description' => 'Unterstützt den Anlass mit Kommunikation und Gestaltung.', 'logo_filename' => 'rohner_spiller.svg', 'url' => 'https://rohnerspiller.ch/'],
            ['name' => 'TM Kommunikation', 'description' => 'Unterstützt den Anlass in der Kommunikation.', 'logo_filename' => 'tm_kommunikation.svg', 'url' => 'https://tmkommunikation.ch/'],
            ['name' => 'Veloplus', 'description' => 'Unterstützt den Anlass rund ums Velo.', 'logo_filename' => 'veloplus.svg', 'url' => 'https://www.veloplus.ch/'],
            ['name' => 'Intersport Egli', 'description' => 'Unterstützt den Anlass rund um Sport und Bewegung.', 'logo_filename' => 'intersport_egli.svg', 'url' => 'https://www.intersport.ch/'],
        ])->map(fn (array $attributes): Sponsor => Sponsor::query()->updateOrCreate(
            ['name' => $attributes['name']],
            $attributes,
        ));

        $faqPivots = collect([
            'general' => ['title' => 'Wann und wo findet der Anlass statt?', 'content_md' => 'Der Anlass findet bei der Brühlgut Stiftung in Winterthur statt. Die genauen Zeiten stehen auf der Startseite.'],
            'athletes' => ['title' => 'Wie kann ich als Sportler:in teilnehmen?', 'content_md' => 'Melde dich über das Anmeldeformular an und wähle deine Sportart sowie eine:n Benefizpartner:in.'],
            'donors' => ['title' => 'Wie kann ich spenden?', 'content_md' => 'Wähle eine:n Sportler:in und lege deinen Spendenbetrag pro Runde fest.'],
            'background' => ['title' => 'Wohin gehen die Spenden?', 'content_md' => 'Die Spenden gehen vollständig an die ausgewählten Benefizpartner:innen.'],
        ])->mapWithKeys(function (array $attributes, string $group): array {
            $faq = Faq::query()->updateOrCreate(['title' => $attributes['title']], $attributes);

            return [$faq->id => ['group' => $group, 'sort_order' => 10, 'is_published' => true]];
        });

        $sportTypePivots = SportType::query()->orderBy('id')->get()->mapWithKeys(
            fn (SportType $sportType, int $index): array => [$sportType->id => ['sort_order' => ($index + 1) * 10, 'is_enabled' => true]],
        );
        $sponsorPivots = $sponsors->values()->mapWithKeys(
            fn (Sponsor $sponsor, int $index): array => [$sponsor->id => [
                'size' => $index === 0 ? 'large' : 'medium',
                'contribution_text' => 'Unterstützt Höhenmeter für Menschen.',
                'sort_order' => ($index + 1) * 10,
                'is_published' => true,
            ]],
        );

        $events->each(function (DonationEvent $event) use ($sportTypePivots, $partners, $sponsorPivots, $faqPivots): void {
            $partnerNames = $event->slug === '2026'
                ? ['Brühlgut Stiftung', 'Vereinigung Begleitung Kranker', 'Stiftung Windlicht']
                : ['Brühlgut Stiftung', 'Institut Kinderseele Schweiz', 'Tel. 143 - Die Dargebotene Hand'];
            $partnerPivots = collect($partnerNames)
                ->map(fn (string $partnerName): Partner => $partners->firstOrFail(fn (Partner $partner): bool => $partner->name === $partnerName))
                ->mapWithKeys(
                    fn (Partner $partner, int $index): array => [$partner->id => ['sort_order' => ($index + 1) * 10, 'is_published' => true]],
                );

            $event->sportTypes()->sync($sportTypePivots->all());
            $event->partners()->sync($partnerPivots->all());
            $event->sponsors()->sync($sponsorPivots->all());
            $event->faqs()->sync($faqPivots->all());
        });
    }

    protected function seedLocalScenario(DonationEvent $pastEvent, DonationEvent $futureEvent): void
    {
        $donorOnlyUsers = ExternalUser::factory()->count(70)->create();
        $athleteOnlyUsers = ExternalUser::factory()->count(20)->create();
        $dualRoleUsers = ExternalUser::factory()->count(10)->create();

        $athletes2025 = $athleteOnlyUsers->random(7)->merge($dualRoleUsers->random(3));
        $athletes2026 = $athleteOnlyUsers->diff($athletes2025)->values()->merge($dualRoleUsers);
        $portalUser = $dualRoleUsers->firstOrFail();

        $registrations2025 = $this->createEventRegistrations($athletes2025, $pastEvent);
        $registrations2026 = $this->createEventRegistrations($athletes2026, $futureEvent);

        $donorPool = $donorOnlyUsers->merge($dualRoleUsers)->values();

        $this->seedEventGroupScenario($registrations2026, $futureEvent);
        $this->createDonationsForEvent($registrations2025, $donorPool, 70);
        $this->createDonationsForEvent($registrations2026, $donorPool, 150);

        $this->seedPortalSmokeScenario($portalUser, $registrations2026, $futureEvent);
    }

    /**
     * @param  Collection<int, AthleteRegistration>  $registrations
     */
    protected function seedEventGroupScenario(Collection $registrations, DonationEvent $event): void
    {
        $soleAdminGroup = EventGroup::factory()
            ->forEvent($event)
            ->create(['name' => 'Winterthur Solo']);
        $this->assignGroupMembership(
            $registrations->firstOrFail(),
            $soleAdminGroup,
            GroupMembershipStatus::Accepted,
            GroupMembershipRole::Admin,
        );

        $multiAdminGroup = EventGroup::factory()
            ->forEvent($event)
            ->create(['name' => 'Gipfelstürmerinnen']);
        $registrations->skip(1)->take(2)->each(
            fn (AthleteRegistration $registration): bool => $this->assignGroupMembership(
                $registration,
                $multiAdminGroup,
                GroupMembershipStatus::Accepted,
                GroupMembershipRole::Admin,
            ),
        );
        $this->assignGroupMembership(
            $registrations->skip(3)->firstOrFail(),
            $multiAdminGroup,
            GroupMembershipStatus::Accepted,
            GroupMembershipRole::Member,
        );

        $pendingGroup = EventGroup::factory()
            ->forEvent($event)
            ->create(['name' => 'Noch offen']);
        $this->assignGroupMembership(
            $registrations->skip(4)->firstOrFail(),
            $pendingGroup,
            GroupMembershipStatus::Accepted,
            GroupMembershipRole::Admin,
        );
        $this->assignGroupMembership(
            $registrations->skip(5)->firstOrFail(),
            $pendingGroup,
            GroupMembershipStatus::Pending,
        );
    }

    protected function assignGroupMembership(
        AthleteRegistration $registration,
        EventGroup $group,
        GroupMembershipStatus $status,
        ?GroupMembershipRole $role = null,
    ): bool {
        return $registration->update([
            'event_group_id' => $group->id,
            'group_membership_status' => $status,
            'group_membership_role' => $role,
        ]);
    }

    /**
     * Reuse local graph records so browser fixtures do not change seeded counts.
     *
     * @param  Collection<int, AthleteRegistration>  $registrations
     */
    protected function seedPortalSmokeScenario(ExternalUser $portalUser, Collection $registrations, DonationEvent $event): void
    {
        $portalUser->update(['email' => 'portal-smoke@example.test', 'first_name' => 'Cédric', 'last_name' => 'Smoke']);

        $portalRegistrationIds = Donation::query()
            ->where('donor_external_user_id', $portalUser->id)
            ->pluck('athlete_registration_id');

        $pendingDonation = Donation::query()
            ->whereRelation('athleteRegistration', 'donation_event_id', $event->id)
            ->whereRelation('athleteRegistration', 'external_user_id', '!=', $portalUser->id)
            ->whereNotIn('athlete_registration_id', $portalRegistrationIds)
            ->firstOrFail();
        $pendingDonation->update([
            'donor_external_user_id' => $portalUser->id,
            'amount_per_round' => 5,
            'amount_min' => 20,
            'amount_max' => 50,
            'verified' => false,
        ]);
    }

    protected function createEventRegistrations(Collection $externalUsers, DonationEvent $event): Collection
    {
        $partnerIds = $event->partners()->pluck('partners.id');

        return $externalUsers->map(function (ExternalUser $externalUser, int $index) use ($event, $partnerIds): AthleteRegistration {
            $factory = AthleteRegistration::factory()
                ->forEvent($event)
                ->forExternalUser($externalUser);

            if ($index % 3 === 0) {
                return $factory->state(['partner_id' => null])->verified()->create();
            }

            return $factory->withPartner($partnerIds->random())->verified()->create();
        });
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

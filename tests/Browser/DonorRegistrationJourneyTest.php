<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\ConfirmDonorRegistration;
use App\Notifications\ContinueDonorRegistration;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('lets a logged in external user donate through the wizard', function (): void {
    Notification::fake();

    [$event, $athleteRegistration] = createDonorWizardOpenEventForBrowserTest();
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'email' => 'francesca@example.com',
    ]);

    actingAs($externalUser, 'external');

    $athleteOption = '[data-test="athlete-option-'.$athleteRegistration->id.'"]';

    visit(route('become-donor'))
        ->assertNoJavaScriptErrors()
        ->assertSee('Bestehendes Profil erkannt')
        ->assertSee('Francesca Arslan')
        ->assertDontSee('Vorname')
        ->click('[data-flux-select-button]')
        ->click($athleteOption)
        ->assertSee('Claudia M. hat geschätzt, 10 Runden zu absolvieren')
        ->type('[wire\:model\.live\.blur="amount_per_round"]', '7.50')
        ->type('[wire\:model\.live\.blur="amount_min"]', '50')
        ->type('[wire\:model\.live\.blur="amount_max"]', '200')
        ->type('[wire\:model\.live\.blur="comment"]', 'Tolle Sache!')
        ->click('[wire\:model\.live="privacy_accepted"]')
        ->wait(0.2)
        ->keys('[wire\:model\.live="privacy_accepted"]', 'Tab')
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten')
        ->assertSee('Wir haben dir eine E-Mail geschickt');

    $donation = Donation::query()
        ->whereBelongsTo($externalUser, 'donorExternalUser')
        ->whereBelongsTo($athleteRegistration)
        ->firstOrFail();

    expect($donation->verified)->toBeFalse()
        ->and($donation->amount_per_round)->toBe(7.50)
        ->and($donation->amount_min)->toBe(50.00)
        ->and($donation->amount_max)->toBe(200.00);

    Notification::assertSentTo(
        $externalUser,
        fn (ConfirmDonorRegistration $notification): bool => str_contains($notification->confirmationUrl, (string) $donation->id),
    );
});

it('lets a returning guest resume donation through a signed login link', function (): void {
    Notification::fake();

    [$event, $athleteRegistration] = createDonorWizardOpenEventForBrowserTest();
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'email' => 'francesca@example.com',
    ]);

    $page = visit(route('become-donor'));

    $page->assertNoJavaScriptErrors()
        ->assertSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->type('[wire\:model\.live\.blur="returning_email"]', 'francesca@example.com')
        ->type('[wire\:model\.live\.blur="returning_email_confirmation"]', 'francesca@example.com')
        ->keys('[wire\:model\.live\.blur="returning_email_confirmation"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->assertSee('Login-Link verschickt');

    $loginUrl = null;
    Notification::assertSentOnDemand(
        ContinueDonorRegistration::class,
        function (ContinueDonorRegistration $notification) use (&$loginUrl): bool {
            $loginUrl = $notification->loginUrl;

            return true;
        },
    );

    expect($loginUrl)->toBeString()->not()->toBeEmpty();

    $athleteOption = '[data-test="athlete-option-'.$athleteRegistration->id.'"]';

    $page->navigate($loginUrl)
        ->assertPathIs('/spenderin-werden')
        ->assertSee('Bestehendes Profil erkannt')
        ->assertSee('Francesca Arslan')
        ->click('[data-flux-select-button]')
        ->click($athleteOption)
        ->type('[wire\:model\.live\.blur="amount_per_round"]', '5.00')
        ->click('[wire\:model\.live="privacy_accepted"]')
        ->keys('[wire\:model\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

    expect(Donation::query()
        ->whereBelongsTo($externalUser, 'donorExternalUser')
        ->whereBelongsTo($athleteRegistration)
        ->exists())->toBeTrue();
});

it('lets a new guest create an external user and donation', function (): void {
    Notification::fake();

    [$event, $athleteRegistration] = createDonorWizardOpenEventForBrowserTest();

    $athleteOption = '[data-test="athlete-option-'.$athleteRegistration->id.'"]';

    $page = visit(route('become-donor'));

    $page->assertNoJavaScriptErrors()
        ->assertSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->type('[wire\:model\.live\.blur="returning_email"]', 'mira@example.com')
        ->type('[wire\:model\.live\.blur="returning_email_confirmation"]', 'mira@example.com')
        ->keys('[wire\:model\.live\.blur="returning_email_confirmation"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->assertSee('Deine Angaben')
        ->type('[wire\:model\.live\.blur="first_name"]', 'Mira')
        ->type('[wire\:model\.live\.blur="last_name"]', 'Keller')
        ->type('[wire\:model\.live\.blur="address"]', 'Zelglistrasse 41')
        ->type('[wire\:model\.live\.blur="zip_code"]', '8406')
        ->type('[wire\:model\.live\.blur="city"]', 'Winterthur')
        ->type('[wire\:model\.live\.blur="phone_national"]', '79 123 45 67')
        ->keys('[wire\:model\.live\.blur="phone_national"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->assertSee('Deine Spende')
        ->click('[data-flux-select-button]')
        ->click($athleteOption)
        ->type('[wire\:model\.live\.blur="amount_per_round"]', '10.00')
        ->click('[wire\:model\.live="privacy_accepted"]')
        ->keys('[wire\:model\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten')
        ->assertSee('Wir haben dir eine E-Mail geschickt');

    $externalUser = ExternalUser::query()->where('email', 'mira@example.com')->firstOrFail();
    $donation = Donation::query()
        ->whereBelongsTo($externalUser, 'donorExternalUser')
        ->whereBelongsTo($athleteRegistration)
        ->firstOrFail();

    expect($externalUser->first_name)->toBe('Mira')
        ->and($externalUser->phone_number)->toBe('+41 79 123 45 67')
        ->and($donation->verified)->toBeFalse()
        ->and($donation->amount_per_round)->toBe(10.00);

    Notification::assertSentTo($externalUser, ConfirmDonorRegistration::class);
});

function createDonorWizardOpenEventForBrowserTest(): array
{
    $event = DonationEvent::factory()->defaults()->create([
        'registration_opens_at' => now()->subDay(),
        'donor_registration_closes_at' => now()->addDay(),
    ]);
    $partner = Partner::factory()->create(['name' => 'Brühlgut Stiftung']);
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $athleteUser = ExternalUser::factory()->create([
        'first_name' => 'Claudia',
        'last_name' => 'Müller',
    ]);

    $event->partners()->attach($partner, [
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $event->sportTypes()->attach($sportType, [
        'sort_order' => 1,
        'is_enabled' => true,
    ]);

    $athleteRegistration = AthleteRegistration::query()->create([
        'donation_event_id' => $event->id,
        'external_user_id' => $athleteUser->id,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_estimated' => 10,
        'rounds_done' => 0,
        'comment' => 'Ich laufe für den guten Zweck!',
        'notify_previous_donors' => false,
        'verified' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    Cache::forget('current_donation_event');

    return [$event, $athleteRegistration];
}

<?php

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\ConfirmAthleteRegistration;
use App\Notifications\ContinueAthleteRegistration;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('lets a logged in external user register and confirm through email link', function (): void {
    Notification::fake();

    [$event, $partner, $sportType] = createWizardOpenEventForBrowserTest();
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'email' => 'francesca@example.com',
    ]);

    actingAs($externalUser, 'external');

    $page = visit(route('become-athlete'));

    $page->assertNoJavaScriptErrors()
        ->assertSee('Bestehendes Profil erkannt')
        ->assertSee('Francesca Arslan')
        ->assertDontSee('Vorname')
        ->click($sportType->name)
        ->type('[wire\\:model\\.live\\.blur="rounds_estimated"]', '12')
        ->click($partner->name)
        ->type('[wire\\:model\\.live\\.blur="comment"]', 'Ich freue mich auf den Lauf.')
        ->click('[wire\\:model\\.live="privacy_accepted"]')
        ->click('Anmeldung absenden')
        ->assertSee('Anmeldung erhalten')
        ->assertSee('Wir haben dir eine E-Mail geschickt');

    $registration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->whereBelongsTo($externalUser)
        ->firstOrFail();

    expect($registration->verified)->toBeFalse();

    Notification::assertSentTo(
        $externalUser,
        fn (ConfirmAthleteRegistration $notification): bool => str_contains($notification->confirmationUrl, (string) $registration->id),
    );

    $confirmationUrl = null;
    Notification::assertSentTo(
        $externalUser,
        function (ConfirmAthleteRegistration $notification) use (&$confirmationUrl): bool {
            $confirmationUrl = $notification->confirmationUrl;

            return true;
        },
    );

    expect($confirmationUrl)->toBeString()->not()->toBeEmpty();

    $page->navigate($confirmationUrl)
        ->assertPathIs('/portal')
        ->assertSee('Verifiziert: Nein')
        ->click('Anmeldung bestätigen')
        ->assertSee('Anmeldung bestätigt')
        ->assertSee('Deine Registrierung als Sportler:in ist bestätigt.');

    expect($registration->refresh()->verified)->toBeTrue();
});

it('lets a returning guest resume registration through a signed login link', function (): void {
    Notification::fake();

    [$event, $partner, $sportType] = createWizardOpenEventForBrowserTest();
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'email' => 'francesca@example.com',
    ]);

    $page = visit(route('become-athlete'));

    $page->assertNoJavaScriptErrors()
        ->assertSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->type('[wire\\:model\\.live\\.blur="returning_email"]', 'francesca@example.com')
        ->type('[wire\\:model\\.live\\.blur="returning_email_confirmation"]', 'francesca@example.com')
        ->click('Weiter')
        ->assertSee('Login-Link verschickt');

    $loginUrl = null;
    Notification::assertSentOnDemand(
        ContinueAthleteRegistration::class,
        function (ContinueAthleteRegistration $notification) use (&$loginUrl): bool {
            $loginUrl = $notification->loginUrl;

            return true;
        },
    );

    expect($loginUrl)->toBeString()->not()->toBeEmpty();

    $page->navigate($loginUrl)
        ->assertPathIs('/sportlerin-werden')
        ->assertSee('Bestehendes Profil erkannt')
        ->assertSee('Francesca Arslan')
        ->click($sportType->name)
        ->type('[wire\\:model\\.live\\.blur="rounds_estimated"]', '12')
        ->click($partner->name)
        ->click('[wire\\:model\\.live="privacy_accepted"]')
        ->click('Anmeldung absenden')
        ->assertSee('Anmeldung erhalten');

    $registration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->whereBelongsTo($externalUser)
        ->firstOrFail();

    expect($registration->verified)->toBeFalse();
});

it('lets a new guest register and confirm through email link', function (): void {
    Notification::fake();

    [$event, $partner, $sportType] = createWizardOpenEventForBrowserTest();

    $page = visit(route('become-athlete'));

    $page->assertNoJavaScriptErrors()
        ->assertSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->type('[wire\\:model\\.live\\.blur="returning_email"]', 'mira@example.com')
        ->type('[wire\\:model\\.live\\.blur="returning_email_confirmation"]', 'mira@example.com')
        ->click('Weiter')
        ->assertSee('Deine Angaben')
        ->type('[wire\\:model\\.live\\.blur="first_name"]', 'Mira')
        ->type('[wire\\:model\\.live\\.blur="last_name"]', 'Keller')
        ->type('[wire\\:model\\.live\\.blur="address"]', 'Zelglistrasse 41')
        ->type('[wire\\:model\\.live\\.blur="zip_code"]', '8406')
        ->type('[wire\\:model\\.live\\.blur="city"]', 'Winterthur')
        ->type('[wire\\:model\\.live\\.blur="phone_number"]', '079 123 45 67')
        ->click('Weiter')
        ->assertSee('Dein sportlicher Einsatz')
        ->click($sportType->name)
        ->type('[wire\\:model\\.live\\.blur="rounds_estimated"]', '10')
        ->click($partner->name)
        ->click('[wire\\:model\\.live="privacy_accepted"]')
        ->click('Anmeldung absenden')
        ->assertSee('Anmeldung erhalten')
        ->assertSee('Wir haben dir eine E-Mail geschickt');

    $externalUser = ExternalUser::query()->where('email', 'mira@example.com')->firstOrFail();
    $registration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->whereBelongsTo($externalUser)
        ->firstOrFail();

    expect($registration->verified)->toBeFalse();

    $confirmationUrl = null;
    Notification::assertSentTo(
        $externalUser,
        function (ConfirmAthleteRegistration $notification) use (&$confirmationUrl): bool {
            $confirmationUrl = $notification->confirmationUrl;

            return true;
        },
    );

    expect($confirmationUrl)->toBeString()->not()->toBeEmpty();

    $page->navigate($confirmationUrl)
        ->assertPathIs('/portal')
        ->assertSee('Verifiziert: Nein')
        ->click('Anmeldung bestätigen')
        ->assertSee('Anmeldung bestätigt')
        ->assertSee('Deine Registrierung als Sportler:in ist bestätigt.');

    expect($registration->refresh()->verified)->toBeTrue();
});

function createWizardOpenEventForBrowserTest(): array
{
    $event = DonationEvent::factory()->defaults()->create([
        'registration_opens_at' => now()->subDay(),
        'athlete_registration_closes_at' => now()->addDay(),
    ]);
    $partner = Partner::factory()->create(['name' => 'Brühlgut Stiftung']);
    $sportType = SportType::query()->create(['name' => 'Laufen']);

    $event->partners()->attach($partner, [
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $event->sportTypes()->attach($sportType, [
        'sort_order' => 1,
        'is_enabled' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    Cache::forget('current_donation_event');

    return [$event, $partner, $sportType];
}

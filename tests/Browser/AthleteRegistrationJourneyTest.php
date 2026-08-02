<?php

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\ConfirmAthleteRegistration;
use App\Notifications\ContinueAthleteRegistration;
use App\Settings\EventSettings;
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
        ->keys('[wire\\:key="sport-type-'.$sportType->id.'"]', 'Space')
        ->wait(0.2)
        ->type('[wire\\:model\\.live\\.blur="rounds_estimated"]', '12')
        ->keys('[wire\\:model\\.live\\.blur="rounds_estimated"]', 'Tab')
        ->wait(0.2)
        ->keys('[wire\\:key="partner-'.$partner->id.'"]', 'Enter')
        ->keys('[wire\\:model\\.live="adult"] ui-radio[value="1"]', 'Enter')
        ->wait(0.2)
        ->type('[wire\\:model\\.live\\.blur="comment"]', 'Ich freue mich auf den Lauf.')
        ->keys('[wire\\:model\\.live\\.blur="comment"]', 'Tab')
        ->wait(0.2)
        ->click('[wire\\:model\\.live="privacy_accepted"]')
        ->keys('[wire\\:model\\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

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

    $page->navigate($confirmationUrl)->assertPathIs('/portal');
    $page->script('window.portalSpaMarker = true');

    $page->pressAndWaitFor('Anmeldung bestätigen', 0.2)
        ->assertSee('Deine Registrierung als Sportler:in ist bestätigt.')
        ->assertSee('Bestätigt')
        ->assertDontSee('Bestätigung ausstehend')
        ->assertNoJavaScriptErrors();

    expect($page->script('window.portalSpaMarker'))->toBeTrue()
        ->and($registration->refresh()->verified)->toBeTrue();
})->flaky();

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
        ->type('[wire\\:model\\.live\\.blur="returning_email"]', 'francesca@example.com')
        ->type('[wire\\:model\\.live\\.blur="returning_email_confirmation"]', 'francesca@example.com')
        ->keys('[wire\\:model\\.live\\.blur="returning_email_confirmation"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
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
        ->keys('[wire\\:key="sport-type-'.$sportType->id.'"]', 'Space')
        ->wait(0.2)
        ->type('[wire\\:model\\.live\\.blur="rounds_estimated"]', '12')
        ->keys('[wire\\:model\\.live\\.blur="rounds_estimated"]', 'Tab')
        ->wait(0.2)
        ->keys('[wire\\:key="partner-'.$partner->id.'"]', 'Enter')
        ->keys('[wire\\:model\\.live="adult"] ui-radio[value="0"]', 'Enter')
        ->wait(0.2)
        ->click('[wire\\:model\\.live="privacy_accepted"]')
        ->keys('[wire\\:model\\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

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
        ->pressAndWaitFor('Anmeldung bestätigen', 0.2)
        ->assertSee('Deine Registrierung als Sportler:in ist bestätigt.')
        ->assertNoJavaScriptErrors();

    expect($registration->refresh()->verified)->toBeTrue();
})->flaky();

it('lets a new guest register and confirm through email link', function (): void {
    Notification::fake();

    [$event, $partner, $sportType] = createWizardOpenEventForBrowserTest();

    $page = visit(route('become-athlete'));

    $page->assertNoJavaScriptErrors()
        ->type('[wire\\:model\\.live\\.blur="returning_email"]', 'mira@example.com')
        ->type('[wire\\:model\\.live\\.blur="returning_email_confirmation"]', 'mira@example.com')
        ->keys('[wire\\:model\\.live\\.blur="returning_email_confirmation"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->type('[wire\\:model\\.live\\.blur="first_name"]', 'Mira')
        ->type('[wire\\:model\\.live\\.blur="last_name"]', 'Keller')
        ->type('[wire\\:model\\.live\\.blur="address"]', 'Zelglistrasse 41')
        ->type('[wire\\:model\\.live\\.blur="zip_code"]', '8406')
        ->type('[wire\\:model\\.live\\.blur="city"]', 'Winterthur')
        ->type('[wire\\:model\\.live\\.blur="phone_number"]', '079 123 45 67')
        ->keys('[wire\\:model\\.live\\.blur="phone_number"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->keys('[wire\\:key="sport-type-'.$sportType->id.'"]', 'Space')
        ->wait(0.2)
        ->type('[wire\\:model\\.live\\.blur="rounds_estimated"]', '10')
        ->keys('[wire\\:model\\.live\\.blur="rounds_estimated"]', 'Tab')
        ->wait(0.2)
        ->keys('[wire\\:key="partner-'.$partner->id.'"]', 'Enter')
        ->keys('[wire\\:model\\.live="adult"] ui-radio[value="1"]', 'Enter')
        ->wait(0.2)
        ->click('[wire\\:model\\.live="privacy_accepted"]')
        ->keys('[wire\\:model\\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

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
        ->pressAndWaitFor('Anmeldung bestätigen', 0.2)
        ->assertSee('Deine Registrierung als Sportler:in ist bestätigt.')
        ->assertNoJavaScriptErrors();

    expect($registration->refresh()->verified)->toBeTrue();
})->flaky();

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

    return [$event, $partner, $sportType];
}

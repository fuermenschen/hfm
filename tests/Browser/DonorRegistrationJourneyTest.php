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
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('lets a logged in external user donate and confirm through the email link', function (): void {
    Notification::fake();

    $athleteRegistration = createDonorWizardOpenEventForBrowserTest();
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'email' => 'francesca@example.com',
    ]);

    actingAs($externalUser, 'external');

    visit(route('become-donor'))
        ->assertNoJavaScriptErrors()
        ->click('[data-flux-select-button]')
        ->click('ui-option[value="'.$athleteRegistration->id.'"]')
        ->wait(0.2)
        ->type('[wire\:model\.live\.blur="amount_per_round"]', '7.50')
        ->type('[wire\:model\.live\.blur="amount_min"]', '50')
        ->type('[wire\:model\.live\.blur="amount_max"]', '200')
        ->type('[wire\:model\.live\.blur="comment"]', 'Tolle Sache!')
        ->click('[wire\:model\.live="privacy_accepted"]')
        ->keys('[wire\:model\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

    $donation = Donation::query()
        ->whereBelongsTo($externalUser, 'donorExternalUser')
        ->whereBelongsTo($athleteRegistration)
        ->firstOrFail();

    expect($donation->verified)->toBeFalse();

    $confirmationUrl = null;
    Notification::assertSentTo(
        $externalUser,
        function (ConfirmDonorRegistration $notification) use (&$confirmationUrl): bool {
            $confirmationUrl = $notification->confirmationUrl;

            return true;
        },
    );

    expect($confirmationUrl)->toBeString()->not()->toBeEmpty();

    $page = visit($confirmationUrl)->assertPathIs('/portal');
    $page->script('window.portalSpaMarker = true');

    $page->click('[wire\\:key="pending-donation-'.$donation->id.'"]:has-text("Spende bestätigen")')
        ->wait(0.2)
        ->assertSee('Deine Spende ist bestätigt.')
        ->assertSee('Bestätigt')
        ->assertDontSee('Bestätigung ausstehend')
        ->assertNoJavaScriptErrors();

    expect($page->script('window.portalSpaMarker'))->toBeTrue()
        ->and($donation->refresh()->verified)->toBeTrue();
})->flaky();

it('lets a returning guest resume donation through a signed login link', function (): void {
    Notification::fake();

    $athleteRegistration = createDonorWizardOpenEventForBrowserTest();
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'email' => 'francesca@example.com',
    ]);

    $page = visit(route('become-donor'));

    $page->assertNoJavaScriptErrors()
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

    $page->navigate($loginUrl)
        ->assertPathIs('/spenderin-werden')
        ->click('[data-flux-select-button]')
        ->click('ui-option[value="'.$athleteRegistration->id.'"]')
        ->wait(0.2)
        ->type('[wire\:model\.live\.blur="amount_per_round"]', '5.00')
        ->click('[wire\:model\.live="privacy_accepted"]')
        ->keys('[wire\:model\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

    $donation = Donation::query()
        ->whereBelongsTo($externalUser, 'donorExternalUser')
        ->whereBelongsTo($athleteRegistration)
        ->firstOrFail();

    expect($donation->verified)->toBeFalse();

    $confirmationUrl = null;
    Notification::assertSentTo(
        $externalUser,
        function (ConfirmDonorRegistration $notification) use (&$confirmationUrl): bool {
            $confirmationUrl = $notification->confirmationUrl;

            return true;
        },
    );

    expect($confirmationUrl)->toBeString()->not()->toBeEmpty();

    $page->navigate($confirmationUrl)
        ->assertPathIs('/portal')
        ->click('[wire\\:key="pending-donation-'.$donation->id.'"]:has-text("Spende bestätigen")')
        ->wait(0.2)
        ->assertSee('Deine Spende ist bestätigt.')
        ->assertNoJavaScriptErrors();

    expect($donation->refresh()->verified)->toBeTrue();
})->flaky();

it('lets a new guest donate and confirm through the email link', function (): void {
    Notification::fake();

    $athleteRegistration = createDonorWizardOpenEventForBrowserTest();

    $page = visit(route('become-donor'));

    $page->assertNoJavaScriptErrors()
        ->type('[wire\:model\.live\.blur="returning_email"]', 'mira@example.com')
        ->type('[wire\:model\.live\.blur="returning_email_confirmation"]', 'mira@example.com')
        ->keys('[wire\:model\.live\.blur="returning_email_confirmation"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->type('[wire\:model\.live\.blur="first_name"]', 'Mira')
        ->type('[wire\:model\.live\.blur="last_name"]', 'Keller')
        ->type('[wire\:model\.live\.blur="address"]', 'Zelglistrasse 41')
        ->type('[wire\:model\.live\.blur="zip_code"]', '8406')
        ->type('[wire\:model\.live\.blur="city"]', 'Winterthur')
        ->type('[wire\:model\.live\.blur="phone_national"]', '79 123 45 67')
        ->keys('[wire\:model\.live\.blur="phone_national"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Weiter', 0.2)
        ->click('[data-flux-select-button]')
        ->click('ui-option[value="'.$athleteRegistration->id.'"]')
        ->wait(0.2)
        ->type('[wire\:model\.live\.blur="amount_per_round"]', '10.00')
        ->click('[wire\:model\.live="privacy_accepted"]')
        ->keys('[wire\:model\.live="privacy_accepted"]', 'Tab')
        ->wait(0.2)
        ->pressAndWaitFor('Anmeldung absenden', 0.2)
        ->assertSee('Anmeldung erhalten');

    $externalUser = ExternalUser::query()->where('email', 'mira@example.com')->firstOrFail();
    $donation = Donation::query()
        ->whereBelongsTo($externalUser, 'donorExternalUser')
        ->whereBelongsTo($athleteRegistration)
        ->firstOrFail();

    expect($donation->verified)->toBeFalse();

    $confirmationUrl = null;
    Notification::assertSentTo(
        $externalUser,
        function (ConfirmDonorRegistration $notification) use (&$confirmationUrl): bool {
            $confirmationUrl = $notification->confirmationUrl;

            return true;
        },
    );

    expect($confirmationUrl)->toBeString()->not()->toBeEmpty();

    $page->navigate($confirmationUrl)
        ->assertPathIs('/portal')
        ->click('[wire\\:key="pending-donation-'.$donation->id.'"]:has-text("Spende bestätigen")')
        ->wait(0.2)
        ->assertSee('Deine Spende ist bestätigt.')
        ->assertNoJavaScriptErrors();

    expect($donation->refresh()->verified)->toBeTrue();
})->flaky();

function createDonorWizardOpenEventForBrowserTest(): AthleteRegistration
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

    return $athleteRegistration;
}

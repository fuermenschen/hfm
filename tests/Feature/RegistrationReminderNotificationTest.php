<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Notifications\AthleteRegistrationReminder;
use App\Notifications\ConfirmAthleteRegistration;
use App\Notifications\ConfirmDonorRegistration;
use App\Notifications\DonorRegistrationReminder;
use Illuminate\Contracts\Queue\ShouldQueue;

it('suppresses athlete reminders after registration confirmation', function (): void {
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Mira']);
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);
    $reminder = new AthleteRegistrationReminder((int) $registration->getKey(), $externalUser->first_name);

    expect($reminder)
        ->toBeInstanceOf(ShouldQueue::class)
        ->and($reminder->shouldSend($externalUser, 'mail'))->toBeTrue();

    AthleteRegistration::query()->whereKey($registration)->update(['verified' => true]);

    expect($reminder->shouldSend($externalUser, 'mail'))->toBeFalse();
});

it('suppresses donor reminders after donation confirmation', function (): void {
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Mira']);
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => false]);
    $reminder = new DonorRegistrationReminder((int) $donation->getKey(), $externalUser->first_name);

    expect($reminder)
        ->toBeInstanceOf(ShouldQueue::class)
        ->and($reminder->shouldSend($externalUser, 'mail'))->toBeTrue();

    Donation::query()->whereKey($donation)->update(['verified' => true]);

    expect($reminder->shouldSend($externalUser, 'mail'))->toBeFalse();
});

it('explains athlete confirmation and links to the portal', function (): void {
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Mira']);
    $registration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);

    $mail = (new AthleteRegistrationReminder((int) $registration->getKey(), $externalUser->first_name))->toMail($externalUser);

    expect($mail->subject)->toBe('Erinnerung: Bitte bestätige deine Sportler:innen-Anmeldung')
        ->and($mail->introLines)->toContain('Mit der Bestätigung stellen wir sicher, dass die Anmeldung wirklich von dir stammt. Ohne Bestätigung können Spender:innen dich nicht auswählen.')
        ->and($mail->actionText)->toBe('Zum Portal')
        ->and($mail->actionUrl)->toBe(route('portal.dashboard'));
});

it('explains donor confirmation and links to the portal', function (): void {
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Mira']);
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => false]);

    $mail = (new DonorRegistrationReminder((int) $donation->getKey(), $externalUser->first_name))->toMail($externalUser);

    expect($mail->subject)->toBe('Erinnerung: Bitte bestätige deine Spende')
        ->and($mail->introLines)->toContain('Mit der Bestätigung stellen wir sicher, dass die Spende wirklich von dir stammt. So verhindern wir, dass eine Rechnung an jemanden geschickt wird, der nicht spenden wollte.')
        ->and($mail->actionText)->toBe('Zum Portal')
        ->and($mail->actionUrl)->toBe(route('portal.dashboard'));
});

it('explains why initial confirmations are required', function (): void {
    $athleteMail = (new ConfirmAthleteRegistration('Mira', 'https://example.com/confirm'))->toMail(new ExternalUser);
    $donorMail = (new ConfirmDonorRegistration('Mira', 'https://example.com/confirm'))->toMail(new ExternalUser);

    expect($athleteMail->introLines)->toContain('Bitte öffne den unten stehenden Link und bestätige deine Registrierung. Damit stellen wir sicher, dass die Anmeldung wirklich von dir stammt.')
        ->and($donorMail->introLines)->toContain('Mit deiner Bestätigung stellen wir sicher, dass die Spende wirklich von dir stammt und keine Rechnung an jemanden geschickt wird, der nicht spenden wollte.');
});

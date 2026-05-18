<?php

use App\Components\LoginForm;
use App\Models\Athlete;
use App\Models\DonationEvent;
use App\Models\Donor;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\NewLoginLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('normalizes email and resolves external user login first', function (): void {
    Notification::fake();

    $normalizedEmail = 'person@example.com';
    $inputEmail = '  PERSON@EXAMPLE.COM  ';

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();

    $athlete = Athlete::factory()->create([
        'email' => $normalizedEmail,
        'donation_event_id' => $event->id,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);
    $donor = Donor::factory()->create([
        'email' => $normalizedEmail,
    ]);
    $externalUser = ExternalUser::factory()->create([
        'email' => $normalizedEmail,
        'first_name' => 'Portal',
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', $inputEmail)
        ->call('save');

    Notification::assertSentOnDemand(NewLoginLink::class, function (NewLoginLink $notification, array $channels, object $notifiable) use ($athlete, $donor, $externalUser): bool {
        expect($notifiable->routes['mail'])->toBe('person@example.com');

        return $notification->first_name === $externalUser->first_name
            && $notification->athlete_login_token === $athlete->login_token
            && $notification->donor_login_token === $donor->login_token
            && $notification->external_user_login_url !== '';
    });
});

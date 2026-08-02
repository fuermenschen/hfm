<?php

use App\Components\LoginForm;
use App\Models\ExternalUser;
use App\Notifications\ContinueAthleteRegistration;
use App\Notifications\ContinueDonorRegistration;
use App\Notifications\NewLoginLink;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('normalizes email and sends external user login link', function (): void {
    Notification::fake();

    $normalizedEmail = 'person@example.com';
    $inputEmail = '  PERSON@EXAMPLE.COM  ';

    $externalUser = ExternalUser::factory()->create([
        'email' => $normalizedEmail,
        'first_name' => 'Portal',
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', $inputEmail)
        ->call('save');

    Notification::assertSentOnDemand(NewLoginLink::class, function (NewLoginLink $notification, array $channels, object $notifiable) use ($externalUser): bool {
        expect($notifiable->routes['mail'])->toBe('person@example.com');

        return $notification->first_name === $externalUser->first_name
            && $notification->user_login_url === ''
            && $notification->external_user_login_url !== '';
    });
});

it('shows a persistent masked login-link confirmation and lets users change their email', function (): void {
    Notification::fake();
    ExternalUser::factory()->create(['email' => 'person@example.com']);

    Livewire::test(LoginForm::class)
        ->set('email', 'person@example.com')
        ->call('save')
        ->assertSet('loginLinkState', 'sent')
        ->assertSet('sentToEmail', 'person@example.com')
        ->assertSee('p***@example.com')
        ->assertSee('Der Link ist 15 Minuten gültig.')
        ->call('changeEmail')
        ->assertSet('loginLinkState', 'form')
        ->assertSet('sentToEmail', null);
});

it('preserves an intended portal destination in a replacement login link', function (): void {
    Notification::fake();
    ExternalUser::factory()->create(['email' => 'person@example.com']);

    Livewire::withQueryParams(['redirect' => 'become-athlete'])
        ->test(LoginForm::class)
        ->set('email', 'person@example.com')
        ->call('save');

    Notification::assertSentOnDemand(NewLoginLink::class, function (NewLoginLink $notification): bool {
        return str_contains($notification->external_user_login_url, 'redirect=become-athlete');
    });
});

it('shows a single validation error as text instead of Array', function (): void {
    Livewire::test(LoginForm::class)
        ->set('email', '')
        ->call('save')
        ->assertDispatched('toast-show', function (string $name, array $params): bool {
            return $params['slots']['heading'] === 'Wir benötigen deine E-Mail-Adresse.';
        });
});

it('rate limits repeated login-link requests', function (): void {
    Notification::fake();
    ExternalUser::factory()->create(['email' => 'rate-limit@example.com']);

    $emailKey = 'login-link:'.hash('sha256', 'rate-limit@example.com');
    $ipKey = 'login-link-ip:'.hash('sha256', '127.0.0.1');
    RateLimiter::clear($emailKey);
    RateLimiter::clear($ipKey);

    Livewire::test(LoginForm::class)
        ->set('email', 'rate-limit@example.com')
        ->call('save')
        ->assertSet('loginLinkState', 'sent')
        ->call('resend')
        ->assertDispatched('toast-show', function (string $name, array $params): bool {
            return $params['slots']['heading'] === 'Bitte warte kurz';
        });

    Notification::assertSentOnDemandTimes(NewLoginLink::class, 1);
});

it('keeps login-link notifications out of the queue', function (): void {
    expect(new NewLoginLink('', '', ''))->not->toBeInstanceOf(ShouldQueue::class)
        ->and(new ContinueAthleteRegistration('', ''))->not->toBeInstanceOf(ShouldQueue::class)
        ->and(new ContinueDonorRegistration('', ''))->not->toBeInstanceOf(ShouldQueue::class);
});

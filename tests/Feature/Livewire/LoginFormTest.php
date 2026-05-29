<?php

use App\Components\LoginForm;
use App\Models\ExternalUser;
use App\Notifications\NewLoginLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

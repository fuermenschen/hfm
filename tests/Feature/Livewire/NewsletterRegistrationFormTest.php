<?php

use App\Components\NewsletterRegistrationForm;
use App\Jobs\RegisterNewsletterSubscriber;
use App\Services\Infomaniak\InfomaniakNewsletterService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('renders successfully', function () {
    Livewire::test(NewsletterRegistrationForm::class)
        ->assertStatus(200);
});

test('requires matching email confirmation', function () {
    Livewire::test(NewsletterRegistrationForm::class)
        ->set('first_name', 'Anna')
        ->set('email', 'anna@example.com')
        ->set('email_confirmation', 'other@example.com')
        ->call('save')
        ->assertHasErrors(['email_confirmation' => 'same']);
});

test('queues newsletter registration job', function () {
    Queue::fake();

    Livewire::test(NewsletterRegistrationForm::class)
        ->set('first_name', 'Anna')
        ->set('email', 'ANNA@EXAMPLE.COM')
        ->set('email_confirmation', 'ANNA@EXAMPLE.COM')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('registrationQueued', true)
        ->assertSet('first_name', null)
        ->assertSet('email', null)
        ->assertSet('email_confirmation', null);

    Queue::assertPushed(RegisterNewsletterSubscriber::class, function (RegisterNewsletterSubscriber $job) {
        return $job->firstName === 'Anna' && $job->email === 'anna@example.com';
    });
});

test('newsletter page is accessible', function () {
    get('/newsletter')
        ->assertOk()
        ->assertSee('newsletter-registration-form');
});

test('association page contains newsletter registration form', function () {
    get('/verein')
        ->assertOk()
        ->assertSee('newsletter-registration-form');
});

test('signed newsletter unsubscribe route shows confirmation page first', function () {
    $service = Mockery::mock(InfomaniakNewsletterService::class);
    $service->shouldNotReceive('unsubscribeSubscriber');

    app()->instance(InfomaniakNewsletterService::class, $service);

    $url = URL::temporarySignedRoute('newsletter.unsubscribe', now()->addDay(), ['email' => 'anna@example.com']);

    get($url)
        ->assertOk()
        ->assertSeeText('Möchtest du die E-Mail-Adresse anna@example.com wirklich vom Newsletter abmelden?')
        ->assertSeeText('Jetzt abmelden');
});

test('signed newsletter unsubscribe route unsubscribes only after post confirmation', function () {
    $service = Mockery::mock(InfomaniakNewsletterService::class);
    $service->shouldReceive('unsubscribeSubscriber')
        ->once()
        ->with('anna@example.com');

    app()->instance(InfomaniakNewsletterService::class, $service);

    $url = URL::temporarySignedRoute('newsletter.unsubscribe', now()->addDay(), ['email' => 'anna@example.com']);

    post($url)
        ->assertRedirect($url);

    get($url)
        ->assertOk()
        ->assertSeeText('Deine E-Mail-Adresse anna@example.com wurde erfolgreich vom Newsletter abgemeldet.');
});

test('newsletter unsubscribe route requires valid signature', function () {
    get(route('newsletter.unsubscribe', ['email' => 'anna@example.com']))
        ->assertForbidden();

    post(route('newsletter.unsubscribe.perform', ['email' => 'anna@example.com']))
        ->assertForbidden();
});

<?php

use App\Components\NewsletterRegistrationForm;
use App\Jobs\RegisterNewsletterSubscriber;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

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
    $this->get('/newsletter')
        ->assertOk()
        ->assertSeeLivewire('newsletter-registration-form');
});

test('association page contains newsletter registration form', function () {
    $this->get('/verein')
        ->assertOk()
        ->assertSeeLivewire('newsletter-registration-form');
});

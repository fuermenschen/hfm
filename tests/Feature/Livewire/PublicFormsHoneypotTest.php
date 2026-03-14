<?php

use App\Components\AssociationDonationForm;
use App\Components\BecomeAthleteForm;
use App\Components\BecomeDonorForm;
use App\Components\ContactForm;
use App\Components\LoginForm;
use App\Components\NewsletterRegistrationForm;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

it('adds honeypot fields to all public form views', function (string $viewPath): void {
    $content = file_get_contents(base_path($viewPath));

    expect($content)->toContain('<x-honeypot livewire-model="extraFields" />');
})->with([
    'resources/views/forms/contact-form.blade.php',
    'resources/views/forms/login-form.blade.php',
    'resources/views/forms/newsletter-registration-form.blade.php',
    'resources/views/forms/become-athlete-form.blade.php',
    'resources/views/forms/become-donor-form.blade.php',
    'resources/views/forms/association-donation-form.blade.php',
]);

it('enables spam protection on all public form components', function (string $componentClass): void {
    $uses = class_uses_recursive($componentClass);

    expect($uses)->toContain(UsesSpamProtection::class);

    $reflection = new ReflectionClass($componentClass);
    expect($reflection->hasProperty('extraFields'))->toBeTrue();

    $content = file_get_contents($reflection->getFileName());
    expect($content)->toContain('$this->protectAgainstSpam();');
})->with([
    'contact form' => [ContactForm::class],
    'login form' => [LoginForm::class],
    'newsletter form' => [NewsletterRegistrationForm::class],
    'become athlete form' => [BecomeAthleteForm::class],
    'become donor form' => [BecomeDonorForm::class],
    'association donation form' => [AssociationDonationForm::class],
]);

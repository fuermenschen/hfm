<?php

namespace App\Components;

use App\Jobs\RegisterNewsletterSubscriber;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Lukeraymonddowning\Honey\Traits\WithHoney;

class NewsletterRegistrationForm extends Component
{
    use WithHoney;

    #[Validate('required', message: 'Wir benötigen deinen Vornamen.')]
    #[Validate('string', message: 'Der Vorname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Vorname darf nicht länger als 255 Zeichen sein.')]
    public ?string $first_name = null;

    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    #[Validate('max:255', message: 'Die E-Mail-Adresse darf nicht länger als 255 Zeichen sein.')]
    public ?string $email = null;

    #[Validate('required', message: 'Wir benötigen die Bestätigung deiner E-Mail-Adresse.')]
    #[Validate('same:email', message: 'Die E-Mail-Adressen stimmen nicht überein.')]
    public ?string $email_confirmation = null;

    public bool $registrationQueued = false;

    public function save(): void
    {
        if (! $this->honeyPasses()) {
            throw ValidationException::withMessages([
                'email' => ['Bitte überprüfe deine Angaben.'],
            ]);
        }

        $this->validate();

        RegisterNewsletterSubscriber::dispatch((string) $this->first_name, strtolower((string) $this->email));

        $this->reset(['first_name', 'email', 'email_confirmation']);
        $this->registrationQueued = true;
    }

    public function render()
    {
        return view('forms.newsletter-registration-form');
    }
}

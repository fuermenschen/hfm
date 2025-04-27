<?php

namespace App\Components;

use Livewire\Attributes\Validate;
use Livewire\Component;

class BecomeMemberForm extends Component
{
    // Name der Firma (optional)
    #[Validate('string', message: 'Der Name der Firma muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Name der Firma darf nicht länger als 255 Zeichen sein.')]
    public ?string $company_name = null;

    // Vorname
    #[Validate('required', message: 'Wir benötigen deinen Vornamen.')]
    #[Validate('string', message: 'Der Vorname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Vorname darf nicht länger als 255 Zeichen sein.')]
    public ?string $first_name = null;

    // Nachname
    #[Validate('required', message: 'Wir benötigen deinen Nachnamen.')]
    #[Validate('string', message: 'Der Nachname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Nachname darf nicht länger als 255 Zeichen sein.')]
    public ?string $last_name = null;

    // Adresse
    #[Validate('required', message: 'Wir benötigen deine Adresse.')]
    #[Validate('string', message: 'Die Adresse muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Adresse darf nicht länger als 255 Zeichen sein.')]
    public ?string $address = null;

    // PLZ
    #[Validate('required', message: 'Wir benötigen deine Postleitzahl.')]
    #[Validate('integer', message: 'Die Postleitzahl muss eine Zahl sein.')]
    #[Validate('digits:4', message: 'Die Postleitzahl muss vier Ziffern haben.')]
    public ?int $zip_code = null;

    // Ort
    #[Validate('required', message: 'Wir benötigen deinen Wohnort.')]
    #[Validate('string', message: 'Der Wohnort muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Wohnort darf nicht länger als 255 Zeichen sein.')]
    public ?string $city = null;

    // E-Mail
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    #[Validate('unique:members,email', message: 'Die E-Mail-Adresse ist bereits registriert.')]
    public ?string $email = null;

    // E-Mail bestätigen
    #[Validate('required', message: 'Wir benötigen die Bestätigung deiner E-Mail-Adresse.')]
    #[Validate('same:email', message: 'Die E-Mail-Adressen stimmen nicht überein.')]
    public ?string $email_confirmation = null;

    // Telefonnummer

    #[Validate('required', message: 'Wir benötigen deine Telefonnummer.')]
    #[Validate('string', message: 'Wir benötigen deine Telefonnummer.')]
    #[Validate('digits:10', message: 'Die Telefonnummer ist ungültig.')]
    public ?string $phone_number = null;

    public function render()
    {
        return view('forms.become-member-form');
    }

    public function submit()
    {
        $this->validate();

        $this->reset();
    }
}

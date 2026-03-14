<?php

namespace App\Components;

use App\Notifications\ContactFormMessage;
use Exception;
use Flux;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class ContactForm extends Component
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    // E-Mail
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;

    // Name
    #[Validate('required', message: 'Wir benötigen deinen Namen.')]
    public ?string $name = null;

    // Message
    #[Validate('required', message: 'Wir benötigen eine Nachricht.')]
    public ?string $message = null;

    public function save(): void
    {
        $this->protectAgainstSpam();

        try {
            $this->validate();
        } catch (ValidationException $e) {

            if ($e->validator->errors()->count() > 1) {
                $title = 'Es sind '.$e->validator->errors()->count().' Fehler aufgetreten.';
                $description = implode('<br>', $e->validator->errors()->all());
            } else {
                $title = $e->validator->errors()->first();
                $description = 'Bitte überprüfe deine Angaben.';
            }

            Flux::toast(heading: $title, text: $description, variant: 'danger');

            return;
        }

        try {

            // send contact form message
            $notification = new ContactFormMessage(
                name: $this->name,
                email: $this->email,
                message: $this->message,
                confirmation_to_sender: false,
            );

            Notification::route('mail', config('mail.from.address'))->notify($notification);

            // send confirmation to sender
            $notification = new ContactFormMessage(
                name: $this->name,
                email: $this->email,
                message: $this->message,
                confirmation_to_sender: true,
            );

            Notification::route('mail', $this->email)->notify($notification);

        } catch (Exception $e) {

            Flux::toast(heading: 'Fehler', text: 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.', variant: 'danger');

            $this->reset('email');

            return;
        }

        Flux::toast(heading: 'E-Mail versendet', text: 'Danke für deine Nachricht. Wir melden uns bald bei dir.', variant: 'success');

        $this->reset([
            'email',
            'name',
            'message',
        ]);
    }

    public function render()
    {
        return view('forms.contact-form');
    }

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
    }
}

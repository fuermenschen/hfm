<?php

namespace App\Components;

use App\Notifications\ContactFormMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Lukeraymonddowning\Honey\Traits\WithHoney;
use WireUi\Traits\Actions;

class ContactForm extends Component
{
    use Actions;
    use WithHoney;

    // E-Mail
    #[Validate("required", message: "Wir benötigen deine E-Mail-Adresse.")]
    #[Validate("email", message: "Bitte gib eine gültige E-Mail-Adresse ein.")]
    public ?string $email = null;

    // Name
    #[Validate("required", message: "Wir benötigen deinen Namen.")]
    public ?string $name = null;

    // Message
    #[Validate("required", message: "Wir benötigen eine Nachricht.")]
    public ?string $message = null;

    public function save(): void
    {
        try {
            if (!$this->honeyPasses()) {
                throw ValidationException::withMessages([
                    'spam' => ['Spam detected'],
                ]);
            }

            $this->validate();
        } catch (ValidationException $e) {

            if ($e->validator->messages()->count() > 1) {
                $title = "Es sind " . $e->validator->messages()->count() . " Fehler aufgetreten.";
                $description = implode('<br>', $e->validator->messages()->all());
            } else {
                $title = $e->validator->messages()->first();
                $description = "Bitte überprüfe deine Angaben.";
            }

            $this->dialog([
                "title" => $title,
                "description" => $description,
                "icon" => "error",
            ]);

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

            $this->dialog([
                "title" => "Fehler",
                "description" => "Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.",
                "icon" => "error",
            ]);

            $this->reset('email');

            return;
        }

        $this->dialog([
            "title" => "E-Mail versendet",
            "description" => "Falls die angegebene E-Mail-Adresse bekannt ist, wurde ein Login-Link versendet. Bitte überprüfe dein Postfach.",
            "icon" => "success",
        ]);

        $this->reset('email');
    }

    public function render()
    {
        return view('forms.contact-form');
    }
}

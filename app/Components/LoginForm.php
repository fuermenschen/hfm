<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donator;
use App\Models\User;
use App\Notifications\NewLoginLink;
use Exception;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Lukeraymonddowning\Honey\Traits\WithHoney;
use WireUi\Traits\Actions;

class LoginForm extends Component
{
    use Actions;
    use WithHoney;

    // E-Mail
    #[Validate("required", message: "Wir benötigen deine E-Mail-Adresse.")]
    #[Validate("email", message: "Bitte gib eine gültige E-Mail-Adresse ein.")]
    public ?string $email = null;

    public function save(): void
    {
        try {
            if ($this->honeyPasses()) {
                $this->addError("Spam detected", "Spam detected.");
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

            // get all login tokens
            $athlete = Athlete::where('email', $this->email)->first();
            $athlete_login_token = $athlete ? $athlete->login_token : "";

            $donator = Donator::where('email', $this->email)->first();
            $donator_login_token = $donator ? $donator->login_token : "";

            $user = User::where('email', $this->email)->first();
            $user_login_token = $user ? $user->login_token : "";

            // get the first name
            if ($athlete) {
                $first_name = $athlete->first_name;
            } elseif ($donator) {
                $first_name = $donator->first_name;
            } elseif ($user) {
                $first_name = $user->first_name;
            }

            if (!$athlete && !$donator && !$user) {

                // add delay to prevent timing attacks
                sleep(2);
            } else {
                // send login link
                $notification = new NewLoginLink(
                    first_name: $first_name,
                    athlete_login_token: $athlete_login_token,
                    donator_login_token: $donator_login_token,
                    user_login_token: "", // TODO: add user login token
                );

                Notification::route('mail', $this->email)->notify($notification);
            }

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

    public function render(): View
    {
        return view('forms.login-form');
    }

    public function redirectHelper(string $url): void
    {
        $this->redirect($url, navigate: true);
    }
}

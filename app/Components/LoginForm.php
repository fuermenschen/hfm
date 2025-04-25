<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donator;
use App\Models\User;
use App\Notifications\NewLoginLink;
use Exception;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
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
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;

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

            if ($e->validator->errors()->count() > 1) {
                $title = 'Es sind ' . $e->validator->errors()->count() . ' Fehler aufgetreten.';
                $description = implode('<br>', $e->validator->errors()->all());
            } else {
                $title = $e->validator->errors()->all();
                $description = 'Bitte überprüfe deine Angaben.';
            }

            $this->dialog([
                'title' => $title,
                'description' => $description,
                'icon' => 'error',
            ]);

            return;
        }

        try {

            // get all login tokens
            $athlete = Athlete::where('email', $this->email)->first();
            $athlete_login_token = $athlete ? $athlete->login_token : '';

            $donator = Donator::where('email', $this->email)->first();
            $donator_login_token = $donator ? $donator->login_token : '';

            $user = User::where('email', $this->email)->first();
            $user_url = '';
            if ($user) {
                $user_uuid = $user->uuid;
                $user_url = URL::temporarySignedRoute('login-uuid', now()->addMinutes(15), ['uuid' => $user_uuid]);
            }

            // get the first name
            if ($athlete) {
                $first_name = $athlete->first_name;
            } elseif ($donator) {
                $first_name = $donator->first_name;
            } elseif ($user) {
                $first_name = $user->name;
            } else {
                $first_name = '';
            }

            if (!$athlete && !$donator && !$user) {

                // add random delay to prevent timing attacks
                $random_delay = rand(0, 3);
                sleep($random_delay);
            } else {
                // send login link
                $notification = new NewLoginLink(
                    first_name: $first_name,
                    athlete_login_token: $athlete_login_token,
                    donator_login_token: $donator_login_token,
                    user_login_url: $user_url,
                );

                Notification::route('mail', $this->email)->notify($notification);
            }

        } catch (Exception $e) {

            $this->dialog([
                'title' => 'Fehler',
                'description' => 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.',
                'icon' => 'error',
            ]);

            $this->reset('email');

            return;
        }

        $this->dialog([
            'title' => 'E-Mail versendet',
            'description' => 'Falls die angegebene E-Mail-Adresse bekannt ist, wurde ein Login-Link versendet. Bitte überprüfe dein Postfach.',
            'icon' => 'success',
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

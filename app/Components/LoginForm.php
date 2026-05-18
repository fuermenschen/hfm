<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donor;
use App\Models\ExternalUser;
use App\Models\User;
use App\Notifications\NewLoginLink;
use Exception;
use Flux;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Sleep;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class LoginForm extends Component
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    // E-Mail
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;

    public function save(): void
    {
        $this->protectAgainstSpam();

        $this->email = trim(mb_strtolower((string) $this->email));

        try {
            $this->validate();
        } catch (ValidationException $validationException) {

            if ($validationException->validator->errors()->count() > 1) {
                $title = 'Es sind '.$validationException->validator->errors()->count().' Fehler aufgetreten.';
                $description = implode('<br>', $validationException->validator->errors()->all());
            } else {
                $title = $validationException->validator->errors()->all();
                $description = 'Bitte überprüfe deine Angaben.';
            }

            Flux::toast(heading: (string) $title, text: $description, variant: 'danger');

            return;
        }

        try {
            $normalizedEmail = $this->email;

            $externalUser = ExternalUser::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();

            // get all login tokens
            $athlete = Athlete::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();
            $athleteLoginToken = $athlete ? $athlete->login_token : '';

            $donor = Donor::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();
            $donorLoginToken = $donor ? $donor->login_token : '';

            $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();
            $userLoginUrl = '';
            if ($user) {
                $userUuid = $user->uuid;
                $userLoginUrl = URL::temporarySignedRoute('login-uuid', now()->addMinutes(15), ['uuid' => $userUuid]);
            }

            $externalUserLoginUrl = '';
            if ($externalUser) {
                $externalUserUuid = $externalUser->uuid;
                $externalUserLoginUrl = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUserUuid]);
            }

            // get the first name
            if ($externalUser) {
                $first_name = $externalUser->first_name;
            } elseif ($athlete) {
                $first_name = $athlete->first_name;
            } elseif ($donor) {
                $first_name = $donor->first_name;
            } elseif ($user) {
                $first_name = $user->name;
            } else {
                $first_name = '';
            }

            if (! $athlete && ! $donor && ! $user && ! $externalUser) {

                // add random delay to prevent timing attacks
                $random_delay = random_int(0, 3);
                Sleep::sleep($random_delay);
            } else {
                // send login link
                $notification = new NewLoginLink(
                    first_name: $first_name,
                    athlete_login_token: $athleteLoginToken,
                    donor_login_token: $donorLoginToken,
                    user_login_url: $userLoginUrl,
                    external_user_login_url: $externalUserLoginUrl,
                );

                Notification::route('mail', $normalizedEmail)->notify($notification);
            }

        } catch (Exception $exception) {

            Flux::toast(heading: 'Fehler', text: 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.', variant: 'danger');

            $this->reset('email');

            return;
        }

        Flux::toast(
            heading: 'E-Mail versendet',
            text: 'Falls die angegebene E-Mail-Adresse bekannt ist, wurde ein Login-Link versendet. Bitte überprüfe dein Postfach.',
            variant: 'success',
        );

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

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
    }
}

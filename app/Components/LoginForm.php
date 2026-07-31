<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\ExternalUser;
use App\Models\User;
use App\Notifications\NewLoginLink;
use Exception;
use Flux;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
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

    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;

    public string $loginLinkState = 'form';

    public ?string $sentToEmail = null;

    public ?int $sentAt = null;

    public ?string $redirect = null;

    public const int RESEND_COOLDOWN_SECONDS = 60;

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
                $title = $validationException->validator->errors()->first();
                $description = 'Bitte überprüfe deine Angaben.';
            }

            Flux::toast(heading: $title, text: $description, variant: 'danger');

            return;
        }

        if (! $this->sendLoginLink()) {
            return;
        }

        $this->enterSentState();
    }

    public function resend(): void
    {
        if ($this->loginLinkState !== 'sent' || $this->sentToEmail === null) {
            return;
        }

        $this->email = $this->sentToEmail;

        if (! $this->sendLoginLink()) {
            return;
        }

        $this->enterSentState();
    }

    public function changeEmail(): void
    {
        $this->loginLinkState = 'form';
        $this->sentToEmail = null;
        $this->sentAt = null;
        $this->resetValidation();
    }

    protected function resendAvailableIn(): int
    {
        if ($this->sentAt === null) {
            return 0;
        }

        return max(0, self::RESEND_COOLDOWN_SECONDS - (time() - $this->sentAt));
    }

    protected function maskedEmail(): string
    {
        $email = $this->sentToEmail ?? $this->email ?? '';

        if ($email === '' || ! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $localPart = $local === '' ? '' : mb_substr($local, 0, 1).'***';

        return $localPart.'@'.$domain;
    }

    public function render(): View
    {
        return view('forms.login-form', [
            'maskedEmail' => $this->maskedEmail(),
            'resendAvailableIn' => $this->resendAvailableIn(),
        ]);
    }

    public function redirectHelper(string $url): void
    {
        $this->redirect($url, navigate: true);
    }

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
        $redirect = request()->query('redirect');
        $this->redirect = in_array($redirect, ['become-athlete', 'become-donor'], true) ? $redirect : null;
    }

    protected function sendLoginLink(): bool
    {
        $normalizedEmail = (string) $this->email;
        $ip = (string) request()->ip();

        $emailKey = 'login-link:'.hash('sha256', $normalizedEmail);
        $ipKey = 'login-link-ip:'.hash('sha256', $ip);

        if (RateLimiter::tooManyAttempts($emailKey, 1)) {
            $this->reportCooldown($emailKey);

            return false;
        }

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $this->reportCooldown($ipKey, burst: true);

            return false;
        }

        RateLimiter::hit($emailKey, self::RESEND_COOLDOWN_SECONDS);
        RateLimiter::hit($ipKey, 600);

        try {
            $externalUser = ExternalUser::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();

            $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])->first();
            $userLoginUrl = '';
            if ($user) {
                $userLoginUrl = URL::temporarySignedRoute('login-uuid', now()->addMinutes(15), ['uuid' => $user->uuid]);
            }

            $externalUserLoginUrl = '';
            if ($externalUser) {
                $parameters = ['uuid' => $externalUser->uuid];

                if (in_array($this->redirect, ['become-athlete', 'become-donor'], true)) {
                    $parameters['redirect'] = $this->redirect;
                }

                $externalUserLoginUrl = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), $parameters);
            }

            if (! $user && ! $externalUser) {
                Sleep::sleep(random_int(0, 3));
            } else {
                $firstName = $externalUser->first_name ?? $user->name ?? '';

                $notification = new NewLoginLink(
                    first_name: $firstName,
                    user_login_url: $userLoginUrl,
                    external_user_login_url: $externalUserLoginUrl,
                );

                Notification::route('mail', $normalizedEmail)->notify($notification);
            }

        } catch (Exception) {
            Flux::toast(heading: 'Fehler', text: 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.', variant: 'danger');

            return false;
        }

        return true;
    }

    protected function enterSentState(): void
    {
        $this->loginLinkState = 'sent';
        $this->sentToEmail = $this->email;
        $this->sentAt = time();
    }

    protected function reportCooldown(string $key, bool $burst = false): void
    {
        $seconds = RateLimiter::availableIn($key);

        Flux::toast(
            heading: 'Bitte warte kurz',
            text: $burst
                ? 'Du hast zu viele Login-Links angefordert. Versuche es in '.$seconds.' Sekunden erneut.'
                : 'Bitte warte '.$seconds.' Sekunden, bevor du erneut einen Link anforderst.',
            variant: 'danger',
        );
    }
}

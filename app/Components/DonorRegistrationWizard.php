<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\CreateDonationAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Notifications\ConfirmDonorRegistration;
use App\Notifications\ContinueDonorRegistration;
use App\Notifications\DonorRegistrationReminder;
use App\Rules\ValidZipCode;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url as LivewireUrl;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class DonorRegistrationWizard extends Component
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    public string $currentStep = 'start';

    public ?string $participation = null;

    #[LivewireUrl(as: 'sportlerin')]
    public ?string $athletePublicId = null;

    #[Validate('required', message: 'Bitte gib deine E-Mail-Adresse ein.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    #[Validate('max:255')]
    public ?string $returning_email = null;

    #[Validate('required', message: 'Bitte bestätige deine E-Mail-Adresse.')]
    #[Validate('same:returning_email', message: 'Die E-Mail-Adressen stimmen nicht überein.')]
    public ?string $returning_email_confirmation = null;

    #[Validate('required', message: 'Wir benötigen deinen Vornamen.')]
    #[Validate('string')]
    #[Validate('max:255')]
    public ?string $first_name = null;

    #[Validate('required', message: 'Wir benötigen deinen Nachnamen.')]
    #[Validate('string')]
    #[Validate('max:255')]
    public ?string $last_name = null;

    #[Validate('required', message: 'Wir benötigen deine Adresse.')]
    #[Validate('string')]
    #[Validate('max:255')]
    public ?string $address = null;

    #[Validate('required', message: 'Wir benötigen deine Postleitzahl.')]
    #[Validate('string')]
    public ?string $zip_code = null;

    #[Validate('required', message: 'Wir benötigen deinen Wohnort.')]
    #[Validate('string')]
    #[Validate('max:255')]
    public ?string $city = null;

    #[Validate('required')]
    #[Validate('in:CH,DE,AT', message: 'Das Land ist ungültig.')]
    public string $country_of_residence = 'CH';

    #[Validate('required', message: 'Bitte wähle die Ländervorwahl.')]
    #[Validate('in:CH,DE,AT', message: 'Die Ländervorwahl ist ungültig.')]
    public string $phone_country = 'CH';

    #[Validate('required', message: 'Wir benötigen deine Telefonnummer.')]
    public ?string $phone_national = null;

    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    #[Validate('max:255')]
    public ?string $email = null;

    #[Validate('required', message: 'Bitte wähle eine:n Sportler:in aus.')]
    #[Validate('integer')]
    public ?int $athlete_registration_id = null;

    #[Validate('required', message: 'Bitte gib einen Betrag ein.')]
    #[Validate('numeric', message: 'Der Betrag muss eine Zahl sein.')]
    #[Validate('min:0.05', message: 'Der Betrag muss mindestens Fr. 0.05 sein.')]
    public ?float $amount_per_round = null;

    #[Validate('nullable')]
    #[Validate('numeric', message: 'Der Betrag muss eine Zahl sein.')]
    #[Validate('min:1.00', message: 'Der Betrag muss mindestens Fr. 1.- sein.')]
    #[Validate('gte:amount_per_round', message: 'Der Betrag muss grösser oder gleich dem Betrag pro Runde sein.')]
    public ?float $amount_max = null;

    #[Validate('nullable')]
    #[Validate('numeric', message: 'Der Betrag muss eine Zahl sein.')]
    #[Validate('min:0.05', message: 'Der Betrag muss mindestens Fr. 0.05 sein.')]
    #[Validate('gte:amount_per_round', message: 'Der Betrag muss grösser oder gleich dem Betrag pro Runde sein.')]
    public ?float $amount_min = null;

    #[Validate('nullable')]
    #[Validate('string')]
    #[Validate('max:2000', message: 'Der Kommentar darf nicht länger als 2000 Zeichen sein.')]
    public ?string $comment = null;

    #[Validate('accepted', message: 'Bitte bestätige, dass wir deine Daten für die Organisation des Anlasses verwenden dürfen.')]
    public bool $privacy_accepted = false;

    public bool $isAuthenticatedExternalUser = false;

    /**
     * @var array<int, array{id: int, display_name: string, privacy_name: string, public_id_string: string, sport_type: string, partner: string|null, rounds_estimated: int|null}>
     */
    public array $athleteRegistrations = [];

    public ?string $currentAthleteName = null;

    public ?string $currentSportType = null;

    public ?string $currentPartner = null;

    public ?int $currentRounds = null;

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
        $this->isAuthenticatedExternalUser = auth()->guard('external')->check();

        if ($this->isAuthenticatedExternalUser) {
            $this->currentStep = 'donation';
            $this->participation = 'returning';
        }

        $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();

        if ($currentDonationEvent === null) {
            return;
        }

        $this->athleteRegistrations = AthleteRegistration::query()
            ->select(['id', 'external_user_id', 'sport_type_id', 'partner_id', 'rounds_estimated'])
            ->whereBelongsTo($currentDonationEvent)
            ->where('verified', true)
            ->with(['externalUser:id,first_name,last_name,public_id', 'sportType:id,name', 'partner:id,name'])
            ->oldest()
            ->get()
            ->map(fn (AthleteRegistration $registration): array => [
                'id' => $registration->id,
                'display_name' => sprintf('%s (%s)', $registration->externalUser->privacyName(), $registration->externalUser->public_id_string),
                'privacy_name' => $registration->externalUser->privacyName(),
                'public_id_string' => $registration->externalUser->public_id_string,
                'sport_type' => $registration->sportType->name,
                'partner' => $registration->partner?->name,
                'rounds_estimated' => $registration->rounds_estimated,
            ])
            ->sortBy('display_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $preselectedAthlete = collect($this->athleteRegistrations)
            ->first(fn (array $registration): bool => Str::replace('-', '', $registration['public_id_string']) === Str::upper(Str::replace('-', '', $this->athletePublicId ?? '')));

        $this->athlete_registration_id = $preselectedAthlete['id'] ?? null;

        $this->updatedAthleteRegistrationId($this->athlete_registration_id);
    }

    public function next(): void
    {
        $shouldLookupEmail = $this->shouldLookupEmail();

        if ($shouldLookupEmail) {
            $this->normalizeLookupEmails();
        }

        $this->validateStep($this->currentStep);

        if ($shouldLookupEmail) {
            $this->protectAgainstSpam();

            try {
                $externalUser = $this->lookupExternalUserByEmail();
            } catch (ValidationException $validationException) {
                $this->setErrorBag($validationException->validator->errors());

                return;
            }

            if ($externalUser instanceof ExternalUser) {
                $this->participation = 'returning';
                $this->currentStep = 'login-link-sent';
            } else {
                $this->participation = 'new';
                $this->email = $this->returning_email;
                $this->currentStep = 'personal';
            }

            $this->dispatch('donor-registration-wizard-step-changed');

            return;
        }

        $nextStep = $this->nextStep();

        if ($nextStep !== null) {
            $this->currentStep = $nextStep;
            $this->dispatch('donor-registration-wizard-step-changed');
        }
    }

    protected function validateStep(string $step): void
    {
        $rules = $this->rulesForStep($step);

        if ($rules === []) {
            return;
        }

        $this->validate($rules, $this->messages());
    }

    /** @return array<string, mixed> */
    protected function rulesForStep(string $step): array
    {
        return match ($step) {
            'start' => $this->isAuthenticatedExternalUser ? [] : [
                'returning_email' => ['required', 'email', 'max:255'],
                'returning_email_confirmation' => ['required', 'same:returning_email'],
            ],
            'personal' => [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:255'],
                'zip_code' => ['required', 'string', new ValidZipCode($this->country_of_residence)],
                'city' => ['required', 'string', 'max:255'],
                'country_of_residence' => ['required', Rule::in(['CH', 'DE', 'AT'])],
                'phone_country' => ['required', Rule::in(['CH', 'DE', 'AT'])],
                'phone_national' => ['required', 'phone:phone_country'],
                'email' => ['required', 'email', 'max:255'],
            ],
            'donation' => [
                'athlete_registration_id' => ['required', 'integer', Rule::in($this->validAthleteRegistrationIds())],
                'amount_per_round' => ['required', 'numeric', 'min:0.05'],
                'amount_min' => ['nullable', 'numeric', 'min:0.05', 'gte:amount_per_round'],
                'amount_max' => ['nullable', 'numeric', 'min:1.00', 'gte:amount_per_round'],
                'comment' => ['nullable', 'string', 'max:2000'],
                'privacy_accepted' => ['accepted'],
            ],
            default => [],
        };
    }

    /** @return array<int, int> */
    protected function validAthleteRegistrationIds(): array
    {
        return array_column($this->athleteRegistrations, 'id');
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'returning_email.required' => 'Bitte gib deine E-Mail-Adresse ein.',
            'returning_email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'returning_email_confirmation.required' => 'Bitte bestätige deine E-Mail-Adresse.',
            'returning_email_confirmation.same' => 'Die E-Mail-Adressen stimmen nicht überein.',
            'first_name.required' => 'Wir benötigen deinen Vornamen.',
            'last_name.required' => 'Wir benötigen deinen Nachnamen.',
            'address.required' => 'Wir benötigen deine Adresse.',
            'zip_code.required' => 'Wir benötigen deine Postleitzahl.',
            'city.required' => 'Wir benötigen deinen Wohnort.',
            'phone_national.required' => 'Wir benötigen deine Telefonnummer.',
            'phone_national.phone' => 'Die Telefonnummer ist ungültig.',
            'country_of_residence.in' => 'Das Land ist ungültig.',
            'email.required' => 'Wir benötigen deine E-Mail-Adresse.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'athlete_registration_id.required' => 'Bitte wähle eine:n Sportler:in aus.',
            'athlete_registration_id.in' => 'Die gewählte Sportler:in ist für den aktuellen Anlass nicht verfügbar.',
            'amount_per_round.required' => 'Bitte gib einen Betrag ein.',
            'amount_per_round.numeric' => 'Der Betrag muss eine Zahl sein.',
            'amount_per_round.min' => 'Der Betrag muss mindestens Fr. 0.05 sein.',
            'amount_min.numeric' => 'Der Betrag muss eine Zahl sein.',
            'amount_min.min' => 'Der Betrag muss mindestens Fr. 0.05 sein.',
            'amount_min.gte' => 'Der Minimalbetrag muss grösser oder gleich dem Betrag pro Runde sein.',
            'amount_max.numeric' => 'Der Betrag muss eine Zahl sein.',
            'amount_max.min' => 'Der Betrag muss mindestens Fr. 1.- sein.',
            'amount_max.gte' => 'Der Maximalbetrag muss grösser oder gleich dem Betrag pro Runde sein.',
            'comment.max' => 'Der Kommentar darf nicht länger als 2000 Zeichen sein.',
            'privacy_accepted.accepted' => 'Bitte bestätige, dass wir deine Daten für die Organisation des Anlasses verwenden dürfen.',
        ];
    }

    protected function shouldLookupEmail(): bool
    {
        return $this->currentStep === 'start'
            && ! $this->isAuthenticatedExternalUser;
    }

    protected function normalizeLookupEmails(): void
    {
        $this->returning_email = trim(mb_strtolower((string) $this->returning_email));
        $this->returning_email_confirmation = trim(mb_strtolower((string) $this->returning_email_confirmation));
    }

    protected function lookupExternalUserByEmail(): ?ExternalUser
    {
        $normalizedEmail = (string) $this->returning_email;

        $rateLimitKey = 'donor-registration-login-link:'.hash('sha256', $normalizedEmail.'|'.request()->ip());
        $ipRateLimitKey = 'donor-registration-login-link-ip:'.hash('sha256', (string) request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 2) || RateLimiter::tooManyAttempts($ipRateLimitKey, 10)) {
            throw ValidationException::withMessages([
                'returning_email' => 'Bitte warte kurz, bevor du erneut einen Link anforderst.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);
        RateLimiter::hit($ipRateLimitKey, 600);

        $startedAt = microtime(true);

        $externalUser = ExternalUser::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->first();

        if ($externalUser instanceof ExternalUser) {
            $loginUrl = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), [
                'uuid' => $externalUser->uuid,
                'redirect' => 'become-donor',
            ]);

            Notification::route('mail', $normalizedEmail)
                ->notify(new ContinueDonorRegistration($externalUser->first_name, $loginUrl));
        }

        $remainingSeconds = $this->emailLookupDelaySeconds() - (microtime(true) - $startedAt);

        if ($remainingSeconds > 0 && app()->isProduction()) {
            Sleep::sleep($remainingSeconds);
        }

        return $externalUser instanceof ExternalUser ? $externalUser : null;
    }

    protected function emailLookupDelaySeconds(): int
    {
        return 3;
    }

    protected function nextStep(): ?string
    {
        $steps = $this->visibleStepKeys();
        $currentIndex = array_search($this->currentStep, $steps, true);

        if (! is_int($currentIndex)) {
            return null;
        }

        return $steps[$currentIndex + 1] ?? null;
    }

    /** @return array<int, string> */
    protected function visibleStepKeys(): array
    {
        return array_keys($this->visibleSteps());
    }

    /** @return array<string, string> */
    protected function visibleSteps(): array
    {
        $steps = [
            'donation' => 'Spende',
        ];

        if (! $this->isAuthenticatedExternalUser) {
            $steps = [
                'start' => 'Start',
                'personal' => 'Person',
                ...$steps,
            ];
        }

        if ($this->currentStep === 'submitted') {
            $steps['submitted'] = 'Bestätigung';
        }

        if ($this->currentStep === 'login-link-sent') {
            $steps['login-link-sent'] = 'Login';
        }

        return $steps;
    }

    public function back(): void
    {
        $previousStep = $this->previousStep();

        if ($previousStep !== null) {
            $this->currentStep = $previousStep;
            $this->resetValidation();
            $this->dispatch('donor-registration-wizard-step-changed');
        }
    }

    protected function previousStep(): ?string
    {
        $steps = $this->visibleStepKeys();
        $currentIndex = array_search($this->currentStep, $steps, true);

        if (! is_int($currentIndex) || $currentIndex === 0) {
            return null;
        }

        return $steps[$currentIndex - 1];
    }

    public function goTo(string $step): void
    {
        if ($this->currentStep === 'submitted' || ($this->currentStep === 'login-link-sent' && $step !== 'start')) {
            return;
        }

        if (! in_array($step, $this->visibleStepKeys(), true)) {
            return;
        }

        $this->currentStep = $step;
        $this->resetValidation();
        $this->dispatch('donor-registration-wizard-step-changed');
    }

    public function restart(): void
    {
        $this->reset([
            'currentStep',
            'participation',
            'returning_email',
            'returning_email_confirmation',
            'first_name',
            'last_name',
            'address',
            'zip_code',
            'city',
            'country_of_residence',
            'phone_country',
            'phone_national',
            'email',
            'athlete_registration_id',
            'amount_per_round',
            'amount_min',
            'amount_max',
            'comment',
            'privacy_accepted',
            'currentAthleteName',
            'currentSportType',
            'currentPartner',
            'currentRounds',
        ]);

        if ($this->isAuthenticatedExternalUser) {
            $this->currentStep = 'donation';
            $this->participation = 'returning';
        }

        $this->resetValidation();
        $this->dispatch('donor-registration-wizard-step-changed');
    }

    public function submit(): void
    {
        $this->protectAgainstSpam();

        if (! $this->isAuthenticatedExternalUser && $this->participation === 'returning') {
            $this->currentStep = 'login-link-sent';
            $this->addError('donation', 'Bitte öffne zuerst den Link in deiner E-Mail, damit wir deine bekannten Angaben verwenden können.');

            return;
        }

        foreach ($this->visibleStepKeys() as $step) {
            $this->validateStep($step);
        }

        if ($this->amount_max && $this->amount_min && $this->amount_max < $this->amount_min) {
            $this->addError('amount_max', 'Der Maximalbetrag muss grösser oder gleich dem Minimalbetrag sein.');

            return;
        }

        if ($this->isAuthenticatedExternalUser && ! $this->createAuthenticatedExternalUserDonation()) {
            return;
        }

        if ($this->participation === 'new' && ! $this->createNewExternalUserDonation()) {
            return;
        }

        $this->currentStep = 'submitted';
        $this->dispatch('donor-registration-wizard-step-changed');
    }

    protected function createAuthenticatedExternalUserDonation(): bool
    {
        $externalUser = $this->externalUser();
        $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();

        if (! $externalUser instanceof ExternalUser || $currentDonationEvent === null) {
            $this->addError('donation', 'Bitte melde dich erneut an.');

            return false;
        }

        try {
            $donation = resolve(CreateDonationAction::class)($currentDonationEvent, $externalUser, [
                'athlete_registration_id' => (int) $this->athlete_registration_id,
                'amount_per_round' => (float) $this->amount_per_round,
                'amount_min' => $this->amount_min,
                'amount_max' => $this->amount_max,
                'comment' => $this->comment,
            ]);
        } catch (ValidationException $validationException) {
            $this->setErrorBag($validationException->validator->errors());

            return false;
        }

        $this->sendConfirmation($donation);

        return true;
    }

    protected function sendConfirmation(Donation $donation): void
    {
        $donation->loadMissing('donorExternalUser');

        $externalUser = $donation->donorExternalUser;
        $confirmationUrl = URL::temporarySignedRoute('portal.donation.confirm', now()->addMinutes(15), [
            'uuid' => $externalUser->uuid,
            'donation' => $donation,
        ]);

        $externalUser->notify(new ConfirmDonorRegistration($externalUser->first_name, $confirmationUrl));

        foreach ([2, 7] as $days) {
            $externalUser->notify(
                new DonorRegistrationReminder((int) $donation->getKey(), $externalUser->first_name)
                    ->delay(now()->addDays($days))
            );
        }
    }

    protected function createNewExternalUserDonation(): bool
    {
        $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();
        $email = trim(mb_strtolower((string) $this->returning_email));

        if ($currentDonationEvent === null) {
            $this->addError('donation', 'Die Anmeldung ist aktuell nicht verfügbar.');

            return false;
        }

        $phoneNumber = CreateDonationAction::formatPhoneNumber((string) $this->phone_national, $this->phone_country);

        try {
            $donation = resolve(CreateDonationAction::class)($currentDonationEvent, null, [
                'athlete_registration_id' => (int) $this->athlete_registration_id,
                'amount_per_round' => (float) $this->amount_per_round,
                'amount_min' => $this->amount_min,
                'amount_max' => $this->amount_max,
                'comment' => $this->comment,
            ], [
                'first_name' => (string) $this->first_name,
                'last_name' => (string) $this->last_name,
                'address' => (string) $this->address,
                'zip_code' => (string) $this->zip_code,
                'city' => (string) $this->city,
                'country_of_residence' => $this->country_of_residence,
                'phone_number' => $phoneNumber,
                'email' => $email,
            ]);
        } catch (ValidationException $validationException) {
            $this->setErrorBag($validationException->validator->errors());

            return false;
        }

        $this->sendConfirmation($donation);

        return true;
    }

    public function updatedAthleteRegistrationId(?int $value): void
    {
        if ($value === null) {
            $this->resetAthleteContext();

            return;
        }

        $registration = array_find($this->athleteRegistrations, fn (array $registration): bool => $registration['id'] === $value);

        if (! is_array($registration)) {
            $this->resetAthleteContext();

            return;
        }

        $this->currentAthleteName = $registration['privacy_name'];
        $this->currentSportType = $registration['sport_type'];
        $this->currentPartner = $registration['partner'] ?? ucfirst(__('app.equal_split'));
        $this->currentRounds = $registration['rounds_estimated'];
    }

    protected function resetAthleteContext(): void
    {
        $this->reset(['currentAthleteName', 'currentSportType', 'currentPartner', 'currentRounds']);
    }

    protected function externalUser(): ?ExternalUser
    {
        $user = auth()->guard('external')->user();

        return $user instanceof ExternalUser ? $user : null;
    }

    public function render(): Factory|View
    {
        $externalUser = $this->externalUser();

        return view('forms.donor-registration-wizard', [
            'displaySteps' => $this->displaySteps(),
            'currentDisplayStepNumber' => $this->currentDisplayStepNumber(),
            'currentStepTitle' => $this->currentStepTitle(),
            'currentStepDescription' => $this->currentStepDescription($externalUser),
            'progressValue' => $this->progressValue(),
            'isFinalStep' => $this->nextStep() === null,
            'canGoBack' => $this->previousStep() !== null,
            'externalUser' => $externalUser,
            'phonePlaceholder' => $this->phonePlaceholder(),
            'zipCodeMask' => $this->zipCodeMask(),
            'zipCodePlaceholder' => $this->zipCodePlaceholder(),
        ]);
    }

    protected function currentStepTitle(): string
    {
        return match ($this->currentStep) {
            'start' => 'Mit welcher E-Mail-Adresse möchtest du dich anmelden?',
            'personal' => 'Deine Angaben',
            'login-link-sent' => 'Bitte prüfe deine E-Mail',
            'donation' => 'Deine Spende',
            'submitted' => 'Anmeldung erhalten',
            default => '',
        };
    }

    protected function currentStepDescription(?ExternalUser $externalUser): string
    {
        if ($this->currentStep === 'donation' && $this->isAuthenticatedExternalUser && $externalUser instanceof ExternalUser) {
            return sprintf('Du meldest dich mit deinem bestehenden Profil als %s an.', $externalUser->full_name);
        }

        return match ($this->currentStep) {
            'start' => 'Wir prüfen, ob bereits ein Profil für dich existiert.',
            'personal' => 'Neue Spender:innen erfassen ihre Kontaktdaten einmalig.',
            'login-link-sent' => 'Wir haben dir einen Link geschickt. Er bringt dich zurück zu dieser Anmeldung.',
            'donation' => 'Wähle eine:n Sportler:in und lege deinen Beitrag fest.',
            'submitted' => 'Bitte prüfe deine E-Mail und bestätige deine Spende im Portal.',
            default => '',
        };
    }

    protected function phonePlaceholder(): string
    {
        return match ($this->phone_country) {
            'DE' => '151 23456789',
            'AT' => '650 1234567',
            default => '79 123 45 67',
        };
    }

    protected function zipCodeMask(): string
    {
        return $this->country_of_residence === 'DE' ? '99999' : '9999';
    }

    protected function zipCodePlaceholder(): string
    {
        return $this->country_of_residence === 'DE' ? '57123' : '8406';
    }

    /** @return array<string, string> */
    protected function displaySteps(): array
    {
        if (in_array($this->currentStep, ['start', 'login-link-sent'], true)) {
            return [];
        }

        $steps = [
            'donation' => 'Spende',
        ];

        if (! $this->isAuthenticatedExternalUser && $this->participation === 'new') {
            $steps = [
                'personal' => 'Person',
                ...$steps,
            ];
        }

        $steps['submitted'] = 'Bestätigung';

        return $steps;
    }

    protected function currentDisplayStepNumber(): int
    {
        $index = array_search($this->currentStep, array_keys($this->displaySteps()), true);

        return is_int($index) ? $index + 1 : 1;
    }

    protected function progressValue(): int
    {
        $steps = $this->displaySteps();

        if ($steps === []) {
            return 0;
        }

        $stepCount = count($steps);

        return (int) round(($this->currentDisplayStepNumber() / $stepCount) * 100);
    }
}

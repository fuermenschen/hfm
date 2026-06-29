<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\CreateAthleteRegistrationAction;
use App\Models\AthleteRegistration;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\ConfirmAthleteRegistration;
use App\Notifications\ContinueAthleteRegistration;
use App\Rules\ValidZipCode;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Sleep;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class AthleteRegistrationWizard extends Component
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    public string $currentStep = 'start';

    public ?string $participation = null;

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
    #[Validate('regex:/^[1-9]\d{3}$/', message: 'Ungültige Postleitzahl')]
    public ?string $zip_code = null;

    #[Validate('required', message: 'Wir benötigen deinen Wohnort.')]
    #[Validate('string')]
    #[Validate('max:255')]
    public ?string $city = null;

    #[Validate('required')]
    #[Validate('in:CH', message: 'Die Anmeldung ist aktuell nur für Personen mit Wohnsitz in der Schweiz möglich.')]
    public string $country_of_residence = 'CH';

    #[Validate('required', message: 'Wir benötigen deine Telefonnummer.')]
    #[Validate('string')]
    #[Validate('regex:/^0\d{2} \d{3} \d{2} \d{2}$/', message: 'Bitte gib eine Schweizer Telefonnummer im Format 079 123 45 67 ein.')]
    public ?string $phone_number = null;

    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    #[Validate('max:255')]
    public ?string $email = null;

    #[Validate('required', message: 'Bitte bestätige deine E-Mail-Adresse.')]
    #[Validate('same:email', message: 'Die E-Mail-Adressen stimmen nicht überein.')]
    public ?string $email_confirmation = null;

    #[Validate('required', message: 'Bitte wähle eine Sportart.')]
    #[Validate('integer')]
    public ?int $sport_type_id = null;

    #[Validate('required', message: 'Bitte gib deine geschätzten Runden an.')]
    #[Validate('integer')]
    #[Validate('min:1', message: 'Die geschätzten Runden müssen mindestens 1 sein.')]
    #[Validate('max:255')]
    public ?int $rounds_estimated = null;

    #[Validate('required', message: 'Bitte wähle, für wen du sammeln möchtest.')]
    #[Validate('integer')]
    public ?int $partner_id = null;

    #[Validate('nullable')]
    #[Validate('string')]
    #[Validate('max:2000', message: 'Der Kommentar darf nicht länger als 2000 Zeichen sein.')]
    public ?string $comment = null;

    #[Validate('boolean')]
    public bool $notify_previous_donors = true;

    #[Validate('accepted', message: 'Bitte bestätige, dass wir deine Daten für die Organisation des Anlasses verwenden dürfen.')]
    public bool $privacy_accepted = false;

    public bool $allowEqualSplitOption = true;

    public bool $isAuthenticatedExternalUser = false;

    /** @var Collection<int, SportType> */
    public Collection $sportTypes;

    /** @var Collection<int, Partner> */
    public Collection $partners;

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
        $this->isAuthenticatedExternalUser = auth()->guard('external')->check();

        if ($this->isAuthenticatedExternalUser) {
            $this->currentStep = 'registration';
            $this->participation = 'returning';
        }

        $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();
        $this->allowEqualSplitOption = (bool) ($currentDonationEvent->has_equal_split_option ?? true);

        $this->sportTypes = $currentDonationEvent?->sportTypes()
            ->wherePivot('is_enabled', true)
            ->orderByPivot('sort_order')
            ->orderBy('sport_types.name')
            ->get() ?? new Collection;

        $this->partners = Partner::query()
            ->when($currentDonationEvent !== null, function ($query) use ($currentDonationEvent): void {
                $query
                    ->join('donation_event_partner', 'donation_event_partner.partner_id', '=', 'partners.id')
                    ->where('donation_event_partner.donation_event_id', $currentDonationEvent->id)
                    ->where('donation_event_partner.is_published', true)
                    ->select('partners.*')
                    ->orderBy('donation_event_partner.sort_order');
            })
            ->orderBy('partners.name')
            ->get();
    }

    public function next(): void
    {
        if ($this->shouldLookupEmail()) {
            $this->normalizeLookupEmails();
        }

        $this->validateStep($this->currentStep);

        if ($this->shouldLookupEmail()) {
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
                $this->email = trim(mb_strtolower((string) $this->returning_email));
                $this->email_confirmation = $this->email;
                $this->currentStep = 'personal';
            }

            $this->dispatch('athlete-registration-wizard-step-changed');

            return;
        }

        $nextStep = $this->nextStep();

        if ($nextStep !== null) {
            $this->currentStep = $nextStep;
            $this->dispatch('athlete-registration-wizard-step-changed');
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
            'personal' => $this->participation === 'new' && ! $this->isAuthenticatedExternalUser ? [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string', 'max:255'],
                'zip_code' => ['required', 'string', new ValidZipCode('CH')],
                'city' => ['required', 'string', 'max:255'],
                'country_of_residence' => ['required', Rule::in(['CH'])],
                'phone_number' => ['required', 'string', 'regex:/^0\d{2} \d{3} \d{2} \d{2}$/'],
                'email' => ['required', 'email', 'max:255'],
                'email_confirmation' => ['required', 'same:email'],
            ] : [],
            'registration' => [
                'sport_type_id' => ['required', 'integer', Rule::in($this->validSportTypeIds())],
                'rounds_estimated' => ['required', 'integer', 'min:1', 'max:255'],
                'partner_id' => ['required', 'integer', Rule::in($this->validPartnerIds())],
                'comment' => ['nullable', 'string', 'max:2000'],
                'privacy_accepted' => [Rule::when(! $this->hasPreviousDonors(), ['accepted'], ['nullable'])],
            ],
            'previous-donors' => [
                'notify_previous_donors' => ['boolean'],
                'privacy_accepted' => ['accepted'],
            ],
            default => [],
        };
    }

    /** @return array<int, int> */
    protected function validSportTypeIds(): array
    {
        return $this->sportTypes->pluck('id')->map(fn (int $id): int => $id)->all();
    }

    /** @return array<int, int> */
    protected function validPartnerIds(): array
    {
        $partnerIds = $this->partners->pluck('id')->map(fn (int $id): int => $id)->all();

        if ($this->allowEqualSplitOption) {
            array_unshift($partnerIds, 0);
        }

        return $partnerIds;
    }

    protected function hasPreviousDonors(): bool
    {
        if ($this->isAuthenticatedExternalUser) {
            $externalUser = $this->externalUser();
            $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();

            if (! $externalUser instanceof ExternalUser || $currentDonationEvent === null) {
                return false;
            }

            return $externalUser->athleteRegistrations()
                ->where('donation_event_id', '!=', $currentDonationEvent->id)
                ->whereHas('donations')
                ->exists();
        }

        return $this->participation === 'returning';
    }

    protected function externalUser(): ?ExternalUser
    {
        $user = auth()->guard('external')->user();

        return $user instanceof ExternalUser ? $user : null;
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
            'phone_number.required' => 'Wir benötigen deine Telefonnummer.',
            'phone_number.regex' => 'Bitte gib eine Schweizer Telefonnummer im Format 079 123 45 67 ein.',
            'country_of_residence.in' => 'Die Anmeldung ist aktuell nur für Personen mit Wohnsitz in der Schweiz möglich.',
            'email.required' => 'Wir benötigen deine E-Mail-Adresse.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'email_confirmation.required' => 'Bitte bestätige deine E-Mail-Adresse.',
            'email_confirmation.same' => 'Die E-Mail-Adressen stimmen nicht überein.',
            'sport_type_id.required' => 'Bitte wähle eine Sportart.',
            'sport_type_id.in' => 'Die gewählte Sportart ist für den aktuellen Anlass nicht verfügbar.',
            'rounds_estimated.required' => 'Bitte gib deine geschätzten Runden an.',
            'rounds_estimated.min' => 'Die geschätzten Runden müssen mindestens 1 sein.',
            'partner_id.required' => 'Bitte wähle, für wen du sammeln möchtest.',
            'partner_id.in' => 'Die gewählte Option ist für den aktuellen Anlass nicht verfügbar.',
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

        $rateLimitKey = 'athlete-registration-login-link:'.hash('sha256', $normalizedEmail.'|'.request()->ip());
        $ipRateLimitKey = 'athlete-registration-login-link-ip:'.hash('sha256', (string) request()->ip());

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
                'redirect' => 'become-athlete',
            ]);

            Notification::route('mail', $normalizedEmail)
                ->notify(new ContinueAthleteRegistration($externalUser->first_name, $loginUrl));
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
            'registration' => 'Sport',
        ];

        if (! $this->isAuthenticatedExternalUser) {
            $steps = [
                'start' => 'Start',
                'personal' => 'Person',
                ...$steps,
            ];
        }

        if ($this->hasPreviousDonors()) {
            $steps['previous-donors'] = 'Frühere Spender:innen';
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
            $this->dispatch('athlete-registration-wizard-step-changed');
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
        $this->dispatch('athlete-registration-wizard-step-changed');
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
            'phone_number',
            'email',
            'email_confirmation',
            'sport_type_id',
            'rounds_estimated',
            'partner_id',
            'comment',
            'notify_previous_donors',
            'privacy_accepted',
        ]);

        $this->resetValidation();
        $this->dispatch('athlete-registration-wizard-step-changed');
    }

    public function submit(): void
    {
        $this->protectAgainstSpam();

        if (! $this->isAuthenticatedExternalUser && $this->participation === 'returning') {
            $this->currentStep = 'login-link-sent';
            $this->addError('registration', 'Bitte öffne zuerst den Link in deiner E-Mail, damit wir deine bekannten Angaben verwenden können.');

            return;
        }

        foreach ($this->visibleStepKeys() as $step) {
            $this->validateStep($step);
        }

        if ($this->isAuthenticatedExternalUser && ! $this->createAuthenticatedExternalUserRegistration()) {
            return;
        }

        if ($this->participation === 'new' && ! $this->createNewExternalUserRegistration()) {
            return;
        }

        $this->currentStep = 'submitted';
        $this->dispatch('athlete-registration-wizard-step-changed');
    }

    protected function createAuthenticatedExternalUserRegistration(): bool
    {
        $externalUser = $this->externalUser();
        $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();

        if (! $externalUser instanceof ExternalUser || $currentDonationEvent === null) {
            $this->addError('registration', 'Bitte melde dich erneut an.');

            return false;
        }

        try {
            $athleteRegistration = resolve(CreateAthleteRegistrationAction::class)($currentDonationEvent, $externalUser, [
                'sport_type_id' => (int) $this->sport_type_id,
                'rounds_estimated' => (int) $this->rounds_estimated,
                'partner_id' => $this->partner_id,
                'comment' => $this->comment,
                'notify_previous_donors' => $this->notify_previous_donors,
            ]);
        } catch (ValidationException $validationException) {
            $this->setErrorBag($validationException->validator->errors());

            return false;
        }

        $this->sendConfirmation($athleteRegistration);

        return true;
    }

    protected function sendConfirmation(AthleteRegistration $athleteRegistration): void
    {
        $athleteRegistration->loadMissing('externalUser');

        $externalUser = $athleteRegistration->externalUser;
        $confirmationUrl = URL::temporarySignedRoute('portal.athlete-registration.confirm', now()->addMinutes(15), [
            'uuid' => $externalUser->uuid,
        ]);

        $externalUser->notify(new ConfirmAthleteRegistration($externalUser->first_name, $confirmationUrl));
    }

    protected function createNewExternalUserRegistration(): bool
    {
        $currentDonationEvent = resolve(CurrentDonationEventService::class)->current();

        if ($currentDonationEvent === null) {
            $this->addError('registration', 'Die Anmeldung ist aktuell nicht verfügbar.');

            return false;
        }

        try {
            $athleteRegistration = resolve(CreateAthleteRegistrationAction::class)($currentDonationEvent, null, [
                'sport_type_id' => (int) $this->sport_type_id,
                'rounds_estimated' => (int) $this->rounds_estimated,
                'partner_id' => $this->partner_id,
                'comment' => $this->comment,
                'notify_previous_donors' => $this->notify_previous_donors,
            ], [
                'first_name' => (string) $this->first_name,
                'last_name' => (string) $this->last_name,
                'address' => (string) $this->address,
                'zip_code' => (string) $this->zip_code,
                'city' => (string) $this->city,
                'country_of_residence' => 'CH',
                'phone_number' => (string) $this->phone_number,
                'email' => (string) $this->email,
            ]);
        } catch (ValidationException $validationException) {
            $this->setErrorBag($validationException->validator->errors());

            return false;
        }

        $this->sendConfirmation($athleteRegistration);

        return true;
    }

    public function updatedPartnerId(?int $value): void
    {
        if ($value === 0 && ! $this->allowEqualSplitOption) {
            $this->partner_id = null;
        }
    }

    public function render(): Factory|View
    {
        return view('forms.athlete-registration-wizard', [
            'steps' => $this->visibleSteps(),
            'currentStepNumber' => $this->currentStepNumber(),
            'progressValue' => $this->progressValue(),
            'hasPreviousDonors' => $this->hasPreviousDonors(),
            'isFinalStep' => $this->nextStep() === null,
            'externalUser' => $this->externalUser(),
        ]);
    }

    protected function currentStepNumber(): int
    {
        $index = array_search($this->currentStep, $this->visibleStepKeys(), true);

        return is_int($index) ? $index + 1 : 1;
    }

    protected function progressValue(): int
    {
        $steps = $this->visibleStepKeys();
        $stepCount = max(count($steps), 1);

        return (int) round(($this->currentStepNumber() / $stepCount) * 100);
    }
}

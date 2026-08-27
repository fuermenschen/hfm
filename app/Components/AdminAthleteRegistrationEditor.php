<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveAthleteRegistrationAction;
use App\Components\Concerns\ConfirmsAdminEdits;
use App\Models\AthleteRegistration;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AdminAthleteRegistrationEditor extends Component
{
    use ConfirmsAdminEdits;

    #[Locked]
    public ?int $athleteRegistrationId = null;

    public bool $modalOpen = false;

    #[Validate('boolean', message: 'Die Volljährigkeit muss wahr oder falsch sein.')]
    public bool $adult = false;

    #[Validate('required', message: 'Bitte gib geschätzte Runden ein.')]
    #[Validate('integer', message: 'Die geschätzten Runden müssen eine ganze Zahl sein.')]
    #[Validate('min:1', message: 'Die geschätzten Runden müssen mindestens 1 sein.')]
    #[Validate('max:255', message: 'Die geschätzten Runden dürfen maximal 255 sein.')]
    public int $roundsEstimated = 1;

    #[Validate('required', message: 'Bitte gib absolvierte Runden ein.')]
    #[Validate('integer', message: 'Die absolvierten Runden müssen eine ganze Zahl sein.')]
    #[Validate('min:0', message: 'Die absolvierten Runden dürfen nicht negativ sein.')]
    #[Validate('max:255', message: 'Die absolvierten Runden dürfen maximal 255 sein.')]
    public int $roundsDone = 0;

    #[Validate('nullable', message: 'Der Kommentar ist ungültig.')]
    #[Validate('string', message: 'Der Kommentar muss ein Text sein.')]
    #[Validate('max:2000', message: 'Der Kommentar darf nicht länger als 2000 Zeichen sein.')]
    public ?string $comment = null;

    #[Validate('boolean', message: 'Die Benachrichtigungseinstellung muss wahr oder falsch sein.')]
    public bool $notifyPreviousDonors = false;

    #[Validate('boolean', message: 'Die Bestätigung muss wahr oder falsch sein.')]
    public bool $verified = false;

    public function render(): Factory|View
    {
        return view('components.admin-athlete-registration-editor');
    }

    #[On('open-athlete-registration-editor')]
    public function open(int $athleteRegistrationId): void
    {
        $this->ensureAuthenticated();
        $this->resetValidation();
        $this->athleteRegistrationId = $athleteRegistrationId;
        $this->fillFromRegistration(AthleteRegistration::query()->findOrFail($athleteRegistrationId));
        $this->captureEditorSnapshot();
        $this->modalOpen = true;

        Flux::modal($this->modalName())->show();
    }

    public function close(): void
    {
        $this->reset();
        $this->resetValidation();

        Flux::modal($this->modalName())->close();
    }

    public function persist(): void
    {
        resolve(SaveAthleteRegistrationAction::class)(AthleteRegistration::query()->findOrFail($this->athleteRegistrationId), [
            'adult' => $this->adult,
            'rounds_estimated' => $this->roundsEstimated,
            'rounds_done' => $this->roundsDone,
            'comment' => $this->comment,
            'notify_previous_donors' => $this->notifyPreviousDonors,
            'verified' => $this->verified,
        ]);

        $this->dispatch('athlete-registration-saved');
        Flux::toast(heading: 'Gespeichert', text: 'Sportler:innen-Anmeldung wurde aktualisiert.', variant: 'success');
    }

    public function modalName(): string
    {
        return 'admin-athlete-registration-editor';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'adult' => $this->adult,
            'roundsEstimated' => $this->roundsEstimated,
            'roundsDone' => $this->roundsDone,
            'comment' => filled($this->comment) ? trim($this->comment) : null,
            'notifyPreviousDonors' => $this->notifyPreviousDonors,
            'verified' => $this->verified,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldLabels(): array
    {
        return [
            'adult' => 'Volljährigkeit',
            'roundsEstimated' => 'Geschätzte Runden',
            'roundsDone' => 'Absolvierte Runden',
            'comment' => 'Kommentar',
            'notifyPreviousDonors' => 'Frühere Spender:innen informieren',
            'verified' => 'Bestätigt',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function logContext(): array
    {
        return ['athlete_registration_id' => $this->athleteRegistrationId];
    }

    protected function fillFromRegistration(AthleteRegistration $athleteRegistration): void
    {
        $this->adult = $athleteRegistration->adult;
        $this->roundsEstimated = $athleteRegistration->rounds_estimated;
        $this->roundsDone = $athleteRegistration->rounds_done;
        $this->comment = $athleteRegistration->comment;
        $this->notifyPreviousDonors = $athleteRegistration->notify_previous_donors;
        $this->verified = $athleteRegistration->verified;
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::guard('web')->check(), 403);
    }
}

<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveDonationAction;
use App\Components\Concerns\ConfirmsAdminEdits;
use App\Models\Donation;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AdminDonationEditor extends Component
{
    use ConfirmsAdminEdits;

    #[Locked]
    public ?int $donationId = null;

    public bool $modalOpen = false;

    #[Validate('required', message: 'Bitte gib einen Betrag pro Runde ein.')]
    #[Validate('numeric', message: 'Der Betrag pro Runde muss eine Zahl sein.')]
    #[Validate('min:0.05', message: 'Der Betrag pro Runde muss mindestens Fr. 0.05 sein.')]
    public ?float $amountPerRound = null;

    #[Validate('nullable', message: 'Der Minimalbetrag ist ungültig.')]
    #[Validate('numeric', message: 'Der Minimalbetrag muss eine Zahl sein.')]
    #[Validate('min:0.05', message: 'Der Minimalbetrag muss mindestens Fr. 0.05 sein.')]
    #[Validate('gte:amountPerRound', message: 'Der Minimalbetrag muss grösser oder gleich dem Betrag pro Runde sein.')]
    public ?float $amountMin = null;

    #[Validate('nullable', message: 'Der Maximalbetrag ist ungültig.')]
    #[Validate('numeric', message: 'Der Maximalbetrag muss eine Zahl sein.')]
    #[Validate('min:1', message: 'Der Maximalbetrag muss mindestens Fr. 1.- sein.')]
    #[Validate('gte:amountPerRound', message: 'Der Maximalbetrag muss grösser oder gleich dem Betrag pro Runde sein.')]
    #[Validate('gte:amountMin', message: 'Der Maximalbetrag muss grösser oder gleich dem Minimalbetrag sein.')]
    public ?float $amountMax = null;

    #[Validate('nullable', message: 'Der Kommentar ist ungültig.')]
    #[Validate('string', message: 'Der Kommentar muss ein Text sein.')]
    #[Validate('max:2000', message: 'Der Kommentar darf nicht länger als 2000 Zeichen sein.')]
    public ?string $comment = null;

    #[Validate('boolean', message: 'Die Bestätigung muss wahr oder falsch sein.')]
    public bool $verified = false;

    public function render(): Factory|View
    {
        return view('components.admin-donation-editor');
    }

    #[On('open-donation-editor')]
    public function open(int $donationId): void
    {
        $this->ensureAuthenticated();
        $this->resetValidation();
        $this->donationId = $donationId;
        $this->fillFromDonation(Donation::query()->findOrFail($donationId));
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
        resolve(SaveDonationAction::class)(Donation::query()->findOrFail($this->donationId), [
            'amount_per_round' => (float) $this->amountPerRound,
            'amount_min' => $this->amountMin,
            'amount_max' => $this->amountMax,
            'comment' => $this->comment,
            'verified' => $this->verified,
        ]);

        $this->dispatch('donation-saved');
        Flux::toast(heading: 'Gespeichert', text: 'Spende wurde aktualisiert.', variant: 'success');
    }

    public function modalName(): string
    {
        return 'admin-donation-editor';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'amountPerRound' => (float) $this->amountPerRound,
            'amountMin' => $this->amountMin,
            'amountMax' => $this->amountMax,
            'comment' => filled($this->comment) ? trim($this->comment) : null,
            'verified' => $this->verified,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldLabels(): array
    {
        return [
            'amountPerRound' => 'Betrag pro Runde',
            'amountMin' => 'Minimaler Betrag',
            'amountMax' => 'Maximaler Betrag',
            'comment' => 'Kommentar',
            'verified' => 'Bestätigt',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function logContext(): array
    {
        return ['donation_id' => $this->donationId];
    }

    protected function fillFromDonation(Donation $donation): void
    {
        $this->amountPerRound = $donation->amount_per_round;
        $this->amountMin = $donation->amount_min;
        $this->amountMax = $donation->amount_max;
        $this->comment = $donation->comment;
        $this->verified = $donation->verified;
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::guard('web')->check(), 403);
    }
}

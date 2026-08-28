<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveSponsorAction;
use App\Models\Sponsor;
use App\Support\AdminFiles\AdminFileStorage;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AdminSponsorEditor extends Component
{
    #[Locked]
    public ?int $sponsorId = null;

    public bool $modalOpen = false;

    #[Validate] // All rules are in rules() because uniqueness is dynamic.
    public string $name = '';

    #[Validate('required', message: 'Bitte gib eine Beschreibung ein.')]
    #[Validate('string', message: 'Die Beschreibung muss ein Text sein.')]
    public string $description = '';

    #[Validate] // All rules are in rules() because available files are dynamic.
    public string $logoFilename = '';

    #[Validate('required', message: 'Bitte gib eine URL ein.')]
    #[Validate('url', message: 'Bitte gib eine gültige URL ein.')]
    #[Validate('max:255', message: 'Die URL darf nicht länger als 255 Zeichen sein.')]
    public string $url = '';

    public function render(): Factory|View
    {
        return view('components.admin-sponsor-editor');
    }

    #[On('open-sponsor-editor')]
    public function open(?int $sponsorId = null): void
    {
        $this->ensureAuthenticated();
        $this->resetValidation();

        if ($sponsorId === null) {
            $this->reset();
        } else {
            $this->sponsorId = $sponsorId;
            $this->fillFromSponsor(Sponsor::query()->findOrFail($sponsorId));
        }

        $this->modalOpen = true;

        Flux::modal($this->modalName())->show();
    }

    public function close(): void
    {
        $this->reset();
        $this->resetValidation();

        Flux::modal($this->modalName())->close();
    }

    public function save(SaveSponsorAction $saveSponsor): void
    {
        $this->ensureAuthenticated();
        $this->name = trim($this->name);
        $this->validate();

        $sponsor = $this->sponsorId === null
            ? null
            : Sponsor::query()->findOrFail($this->sponsorId);
        $isCreating = $sponsor === null;

        $saveSponsor($sponsor, [
            'name' => $this->name,
            'description' => $this->description,
            'logo_filename' => $this->logoFilename,
            'url' => $this->url,
        ]);

        $this->close();
        $this->dispatch('sponsor-saved');

        Flux::toast(
            heading: 'Gespeichert',
            text: $isCreating ? 'Sponsor:in wurde erstellt.' : 'Sponsor:in wurde aktualisiert.',
            variant: 'success',
        );
    }

    public function modalName(): string
    {
        return 'admin-sponsor-editor';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $logoPaths = $this->sponsorLogoPaths();
        $nameRule = Rule::unique('sponsors', 'name');

        if ($this->sponsorId !== null) {
            $nameRule->ignore($this->sponsorId);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'logoFilename' => ['required', 'string', 'max:255', Rule::in($this->allowedSponsorLogoPaths($logoPaths))],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Bitte gib einen Namen ein.',
            'name.string' => 'Der Name muss ein Text sein.',
            'name.max' => 'Der Name darf nicht länger als 255 Zeichen sein.',
            'name.unique' => 'Dieser Name wird bereits verwendet.',
            'logoFilename.required' => 'Bitte wähle ein Logo aus.',
            'logoFilename.string' => 'Das Logo muss ein Dateipfad sein.',
            'logoFilename.max' => 'Der Pfad zum Logo darf nicht länger als 255 Zeichen sein.',
            'logoFilename.in' => 'Bitte wähle ein verfügbares Logo aus.',
        ];
    }

    protected function fillFromSponsor(Sponsor $sponsor): void
    {
        $this->name = $sponsor->name;
        $this->description = $sponsor->description;
        $this->logoFilename = $sponsor->logo_filename;
        $this->url = $sponsor->url;
    }

    /**
     * @return array<int, string>
     */
    protected function sponsorLogoPaths(): array
    {
        return collect(resolve(AdminFileStorage::class)->files('sponsors', recursive: true, extensions: ['svg', 'png', 'jpg', 'jpeg', 'webp']))
            ->pluck('path')
            ->map(fn (string $path): string => str($path)->after('sponsors/')->toString())
            ->all();
    }

    /**
     * @param  array<int, string>  $logoPaths
     * @return array<int, string>
     */
    protected function allowedSponsorLogoPaths(array $logoPaths): array
    {
        $currentPath = $this->sponsorId === null
            ? null
            : Sponsor::query()->whereKey($this->sponsorId)->value('logo_filename');

        return array_values(array_unique(is_string($currentPath) ? [...$logoPaths, $currentPath] : $logoPaths));
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::guard('web')->check(), 403);
    }
}

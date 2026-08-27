<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SavePartnerAction;
use App\Models\Partner;
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

class AdminPartnerEditor extends Component
{
    #[Locked]
    public ?int $partnerId = null;

    public bool $modalOpen = false;

    #[Validate] // All rules are in rules() because uniqueness is dynamic.
    public string $name = '';

    #[Validate] // All rules are in rules() because available files are dynamic.
    public string $logoLightFilename = '';

    #[Validate] // All rules are in rules() because available files are dynamic.
    public string $logoDarkFilename = '';

    #[Validate('required', message: 'Bitte gib einen Kurztext ein.')]
    #[Validate('string', message: 'Der Kurztext muss ein Text sein.')]
    public string $beneficiaryBlurb = '';

    #[Validate('required', message: 'Bitte gib eine URL ein.')]
    #[Validate('url', message: 'Bitte gib eine gültige URL ein.')]
    #[Validate('max:255', message: 'Die URL darf nicht länger als 255 Zeichen sein.')]
    public string $url = '';

    public function render(): Factory|View
    {
        return view('components.admin-partner-editor');
    }

    #[On('open-partner-editor')]
    public function open(?int $partnerId = null): void
    {
        $this->ensureAuthenticated();
        $this->resetValidation();

        if ($partnerId === null) {
            $this->reset();
        } else {
            $this->partnerId = $partnerId;
            $this->fillFromPartner(Partner::query()->findOrFail($partnerId));
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

    public function save(SavePartnerAction $savePartner): void
    {
        $this->ensureAuthenticated();
        $this->name = trim($this->name);
        $this->validate();

        $partner = $this->partnerId === null
            ? null
            : Partner::query()->findOrFail($this->partnerId);
        $isCreating = $partner === null;

        $savePartner($partner, [
            'name' => $this->name,
            'logo_light_filename' => $this->logoLightFilename,
            'logo_dark_filename' => $this->logoDarkFilename,
            'beneficiary_blurb' => $this->beneficiaryBlurb,
            'url' => $this->url,
        ]);

        $this->close();
        $this->dispatch('partner-saved');

        Flux::toast(
            heading: 'Gespeichert',
            text: $isCreating ? 'Partner:in wurde erstellt.' : 'Partner:in wurde aktualisiert.',
            variant: 'success',
        );
    }

    public function modalName(): string
    {
        return 'admin-partner-editor';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $logoPaths = $this->partnerLogoPaths();
        $nameRule = Rule::unique('partners', 'name');

        if ($this->partnerId !== null) {
            $nameRule->ignore($this->partnerId);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'logoLightFilename' => ['required', 'string', 'max:255', Rule::in($this->allowedPartnerLogoPaths($logoPaths, 'logo_light_filename'))],
            'logoDarkFilename' => ['required', 'string', 'max:255', Rule::in($this->allowedPartnerLogoPaths($logoPaths, 'logo_dark_filename'))],
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
            'logoLightFilename.required' => 'Bitte wähle ein helles Logo aus.',
            'logoLightFilename.string' => 'Das helle Logo muss ein Dateipfad sein.',
            'logoLightFilename.max' => 'Der Pfad zum hellen Logo darf nicht länger als 255 Zeichen sein.',
            'logoLightFilename.in' => 'Bitte wähle ein verfügbares helles Logo aus.',
            'logoDarkFilename.required' => 'Bitte wähle ein dunkles Logo aus.',
            'logoDarkFilename.string' => 'Das dunkle Logo muss ein Dateipfad sein.',
            'logoDarkFilename.max' => 'Der Pfad zum dunklen Logo darf nicht länger als 255 Zeichen sein.',
            'logoDarkFilename.in' => 'Bitte wähle ein verfügbares dunkles Logo aus.',
        ];
    }

    protected function fillFromPartner(Partner $partner): void
    {
        $this->name = $partner->name;
        $this->logoLightFilename = $partner->logo_light_filename;
        $this->logoDarkFilename = $partner->logo_dark_filename;
        $this->beneficiaryBlurb = $partner->beneficiary_blurb;
        $this->url = $partner->url;
    }

    /**
     * @return array<int, string>
     */
    protected function partnerLogoPaths(): array
    {
        return collect(resolve(AdminFileStorage::class)->files('partners', recursive: true, extensions: ['svg', 'png', 'jpg', 'jpeg', 'webp']))
            ->pluck('path')
            ->map(fn (string $path): string => str($path)->after('partners/')->toString())
            ->all();
    }

    /**
     * @param  array<int, string>  $logoPaths
     * @return array<int, string>
     */
    protected function allowedPartnerLogoPaths(array $logoPaths, string $field): array
    {
        $currentPath = $this->partnerId === null
            ? null
            : Partner::query()->whereKey($this->partnerId)->value($field);

        return array_values(array_unique(is_string($currentPath) ? [...$logoPaths, $currentPath] : $logoPaths));
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::guard('web')->check(), 403);
    }
}

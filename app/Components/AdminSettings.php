<?php

declare(strict_types=1);

namespace App\Components;

use App\Services\SettingsService;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdminSettings extends Component
{
    public string $activeTab = 'overview';

    public array $classes = [];

    /**
     * Bindable values map: [ FQCN => [ setting => value ] ]
     *
     * @var array<string, array<string, mixed>>
     */
    public array $values = [];

    /**
     * Pending confirmation state
     */
    public ?string $pendingClass = null;

    /**
     * @var array<string,mixed>
     */
    public array $pendingValues = [];

    private mixed $settingsService;

    public function __construct()
    {
        $this->settingsService = resolve(SettingsService::class);
    }

    public function mount(): void
    {
        $this->classes = $this->settingsService->getAllSettings();

        // Initialize bindable values from current settings
        foreach ($this->classes as $class => $meta) {
            foreach ($meta['settings'] as $name => $info) {
                $this->values[$class][$name] = $info['value'] ?? null;
            }
        }
    }

    public function render(): Factory|View
    {
        return view('components.admin.settings');
    }

    public function saveClass(string $class): void
    {
        if (! isset($this->classes[$class]['settings']) || ! is_array($this->classes[$class]['settings'])) {
            return;
        }

        $changed = [];
        $rules = [];
        $attributes = [];

        foreach ($this->classes[$class]['settings'] as $name => $info) {
            $currentValue = $this->values[$class][$name] ?? null;
            $originalValue = $info['value'] ?? null;

            if ($currentValue === $originalValue) {
                continue;
            }

            $key = sprintf('values.%s.%s', $class, $name);
            $rule = $info['rules'] ?? null;
            if (! empty($rule)) {
                $rules[$key] = $rule;
                $attributes[$key] = $info['title'] ?? $name;
            }

            $changed[$name] = $this->normalizeValueForType(
                value: $currentValue,
                type: $info['type'] ?? null,
            );
        }

        if ($changed === []) {
            return;
        }

        if ($rules !== []) {
            try {
                $this->validate($rules, [], $attributes);
            } catch (ValidationException $e) {
                Flux::toast([
                    'heading' => 'Fehler',
                    'text' => (string) $e->validator->errors()->first(),
                    'variant' => 'danger',
                ]);
                throw $e;
            }
        }

        $this->pendingClass = $class;
        $this->pendingValues = $changed;

        Flux::modal('admin-setting-confirm')->show();
    }

    public function commitPending(): void
    {
        if ($this->pendingClass === null || $this->pendingValues === []) {
            return;
        }

        $class = $this->pendingClass;

        try {
            $this->settingsService->save([
                $class => $this->pendingValues,
            ]);
        } catch (\Throwable $throwable) {
            Flux::toast([
                'heading' => 'Fehler',
                'text' => 'Speichern fehlgeschlagen: '.$throwable->getMessage(),
                'variant' => 'danger',
            ]);
            Flux::modal('admin-setting-confirm')->close();

            return;
        }

        foreach ($this->pendingValues as $name => $value) {
            $this->classes[$class]['settings'][$name]['value'] = $value;
            $this->values[$class][$name] = $value;
        }

        Flux::modal('admin-setting-confirm')->close();
        Flux::toast(heading: 'Gespeichert', text: 'Einstellungen wurden gespeichert.', variant: 'success');

        $this->pendingClass = null;
        $this->pendingValues = [];
    }

    public function cancelPending(): void
    {
        $this->pendingClass = null;
        $this->pendingValues = [];

        Flux::modal('admin-setting-confirm')->close();
    }

    protected function normalizeValueForType(mixed $value, ?string $type): mixed
    {
        if ($type === '?int' && $value === '') {
            return null;
        }

        if ($type === 'int' && $value === '') {
            return 0;
        }

        if ($type === 'array' && \is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
                return $decoded;
            }

            return array_map('trim', array_filter(explode(',', $value), fn ($v): bool => $v !== ''));
        }

        return $value;
    }
}

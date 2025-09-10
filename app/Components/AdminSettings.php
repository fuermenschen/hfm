<?php

namespace App\Components;

use App\Services\SettingsService;
use Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdminSettings extends Component
{
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

    public ?string $pendingName = null;

    public mixed $pendingValue = null;

    private mixed $settingsService;

    public function __construct()
    {
        $this->settingsService = app(SettingsService::class);
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

    public function render()
    {
        return view('components.admin.settings');
    }

    public function saveSingle(string $class, string $name): void
    {
        $value = $this->values[$class][$name] ?? null;

        // Validate against provided rules (if any)
        $rule = $this->classes[$class]['settings'][$name]['rules'] ?? null;
        if (! empty($rule)) {
            $key = "values.$class.$name";
            $attr = $this->classes[$class]['settings'][$name]['title'] ?? $name;
            try {
                $this->validate([
                    $key => $rule,
                ], [], [
                    $key => $attr,
                ]);
            } catch (ValidationException $e) {
                // Show a toast with the first validation error and rethrow to keep inline errors
                $message = $e->validator?->errors()->first($key) ?? $e->getMessage();
                Flux::toast([
                    'heading' => 'Fehler',
                    'text' => $message,
                    'variant' => 'danger',
                ]);
                throw $e;
            }
            // Pull the possibly cast/deferred input again after validation
            $value = $this->values[$class][$name] ?? $value;
        }

        // Best-effort: normalize arrays when bound via textarea (string input)
        $type = $this->classes[$class]['settings'][$name]['type'] ?? null;
        if ($type === 'array' && \is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
                $value = $decoded;
            } else {
                // fallback: comma-separated list
                $parts = array_map('trim', array_filter(explode(',', $value), fn ($v) => $v !== ''));
                $value = $parts;
            }
        }

        // Store pending state and open confirmation modal instead of saving immediately
        $this->pendingClass = $class;
        $this->pendingName = $name;
        $this->pendingValue = $value;

        Flux::modal('admin-setting-confirm')->show();
    }

    public function commitPending(): void
    {
        if ($this->pendingClass === null || $this->pendingName === null) {
            return;
        }

        try {
            $this->settingsService->save([
                $this->pendingClass => [
                    $this->pendingName => $this->pendingValue,
                ],
            ]);
        } catch (\Throwable $e) {
            Flux::toast([
                'heading' => 'Fehler',
                'text' => 'Speichern fehlgeschlagen: '.$e->getMessage(),
                'variant' => 'danger',
            ]);
            Flux::modal('admin-setting-confirm')->close();

            return;
        }

        // Refresh current values from source of truth
        $this->mount();

        // Close the modal and show success toast
        Flux::modal('admin-setting-confirm')->close();
        $name = $this->pendingName;
        Flux::toast(heading: 'Gespeichert', text: "Einstellung '$name' wurde gespeichert.", variant: 'success');

        // Clear pending state
        $this->pendingClass = null;
        $this->pendingName = null;
        $this->pendingValue = null;
    }

    public function cancelPending(): void
    {
        $this->pendingClass = null;
        $this->pendingName = null;
        $this->pendingValue = null;

        Flux::modal('admin-setting-confirm')->close();

        // reset the values to the current state
        $this->mount();
    }
}

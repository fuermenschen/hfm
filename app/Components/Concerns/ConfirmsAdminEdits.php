<?php

declare(strict_types=1);

namespace App\Components\Concerns;

use Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Guards admin editor saves behind an explicit confirmation of the changed fields.
 *
 * Editors provide normalized form data, German field labels, the persist routine,
 * a subject phrase for the confirmation sentence, and log context. The trait diffs
 * the data against a snapshot taken on open(), shows a confirmation modal with the
 * before/after values, and writes one audit log entry per confirmed save.
 */
trait ConfirmsAdminEdits
{
    public bool $confirmingSave = false;

    /** @var array<string, mixed> */
    public array $editorSnapshot = [];

    public function confirmModalName(): string
    {
        return $this->modalName().'-confirm';
    }

    /**
     * Normalized current form data with the same keys and value shapes as the data passed to the save action.
     *
     * @return array<string, mixed>
     */
    abstract protected function formData(): array;

    /**
     * German labels for every editable field, keyed by form data key.
     *
     * @return array<string, string>
     */
    abstract protected function fieldLabels(): array;

    /**
     * Dative phrase naming the edited record for the confirmation sentence, e.g. "Max Muster"
     * or "der Spende von Max Muster". Rendered as "Folgende Angaben werden bei … geändert:".
     */
    abstract public function confirmSubject(): string;

    abstract protected function persist(): void;

    /**
     * Record identifiers for the audit log entry.
     *
     * @return array<string, mixed>
     */
    abstract protected function logContext(): array;

    /**
     * Flux modal name of the editor modal; also the base name of the confirm modal.
     */
    abstract public function modalName(): string;

    abstract public function close(): void;

    abstract protected function ensureAuthenticated(): void;

    protected function captureEditorSnapshot(): void
    {
        $this->editorSnapshot = $this->formData();
        $this->confirmingSave = false;
    }

    /**
     * Normalizes writable properties before validation, mirroring the normalization the save action applies.
     */
    protected function prepareValidation(): void {}

    /**
     * @return array<int, string>
     */
    public function changedFieldKeys(): array
    {
        $current = $this->formData();

        return collect($this->fieldLabels())
            ->keys()
            ->filter(fn (string $field): bool => ($current[$field] ?? null) !== ($this->editorSnapshot[$field] ?? null))
            ->values()
            ->all();
    }

    /**
     * Changed fields with German labels and before/after values for the confirmation modal.
     *
     * @return array<int, array{label: string, before: string, after: string}>
     */
    public function changedFields(): array
    {
        $current = $this->formData();

        return collect($this->changedFieldKeys())
            ->map(fn (string $field): array => [
                'label' => $this->fieldLabels()[$field],
                'before' => $this->formatEditorValue($this->editorSnapshot[$field] ?? null),
                'after' => $this->formatEditorValue($current[$field] ?? null),
            ])
            ->all();
    }

    // Value formatting for the confirmation modal. Override in the editor when a field needs custom formatting.
    protected function formatEditorValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'Ja' : 'Nein',
            $value === null => '—',
            default => (string) $value,
        };
    }

    public function save(): void
    {
        $this->ensureAuthenticated();

        if ($this->changedFieldKeys() === []) {
            $this->close();
            Flux::toast(heading: 'Keine Änderungen', text: 'Es wurden keine Angaben geändert.', variant: 'info');

            return;
        }

        $this->prepareValidation();
        $this->validate();
        $this->confirmingSave = true;

        Flux::modal($this->confirmModalName())->show();
    }

    public function cancelSave(): void
    {
        $this->confirmingSave = false;

        Flux::modal($this->confirmModalName())->close();
    }

    public function confirmSave(): void
    {
        $this->ensureAuthenticated();
        $this->prepareValidation();
        $this->validate();

        $changedFieldKeys = $this->changedFieldKeys();

        $this->persist();

        Log::info('Admin editor save confirmed.', [
            'editor' => class_basename(static::class),
            'fields' => $changedFieldKeys,
            'admin' => Auth::guard('web')->user()?->name,
            ...$this->logContext(),
        ]);

        $this->cancelSave();
        $this->close();
    }
}

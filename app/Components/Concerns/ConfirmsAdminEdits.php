<?php

declare(strict_types=1);

namespace App\Components\Concerns;

use Flux;
use Illuminate\Support\Facades\Log;

/**
 * Guards admin editor saves behind an explicit confirmation of the changed fields.
 *
 * Editors provide normalized form data, German field labels, the persist routine,
 * and log context. The trait diffs the data against a snapshot taken on open(),
 * shows a confirmation modal, and writes one audit log entry per confirmed save.
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

    abstract protected function persist(): void;

    /**
     * Record identifiers for the audit log entry.
     *
     * @return array<string, mixed>
     */
    abstract protected function logContext(): array;

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
     * @return array<int, string>
     */
    public function changedFieldLabels(): array
    {
        return collect($this->changedFieldKeys())
            ->map(fn (string $field): string => $this->fieldLabels()[$field])
            ->all();
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
            ...$this->logContext(),
        ]);

        $this->cancelSave();
        $this->close();
    }
}

<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveFaqAction;
use App\Components\Concerns\ConfirmsAdminEdits;
use App\Models\Faq;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AdminFaqEditor extends Component
{
    use ConfirmsAdminEdits;

    #[Locked]
    public ?int $faqId = null;

    public bool $modalOpen = false;

    #[Validate('required', message: 'Bitte gib eine Frage ein.')]
    #[Validate('string', message: 'Die Frage muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Frage darf nicht länger als 255 Zeichen sein.')]
    public string $title = '';

    #[Validate('required', message: 'Bitte gib eine Antwort ein.')]
    #[Validate('string', message: 'Die Antwort muss ein Text sein.')]
    public string $contentMd = '';

    public function render(): Factory|View
    {
        return view('components.admin-faq-editor');
    }

    /**
     * Renders the answer exactly like the public questions-and-answers page.
     */
    #[Computed]
    public function contentHtml(): string
    {
        return Str::markdown(trim($this->contentMd), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    #[On('open-faq-editor')]
    public function open(?int $faqId = null): void
    {
        $this->ensureAuthenticated();
        $this->resetValidation();

        if ($faqId === null) {
            $this->reset();
        } else {
            $this->faqId = $faqId;
            $this->fillFromFaq(Faq::query()->findOrFail($faqId));
        }

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
        $faq = $this->faqId === null
            ? null
            : Faq::query()->findOrFail($this->faqId);
        $isCreating = $faq === null;

        $savedFaq = resolve(SaveFaqAction::class)($faq, [
            'title' => $this->title,
            'content_md' => $this->contentMd,
        ]);

        $this->faqId = $savedFaq->id;
        $this->dispatch('faq-saved');

        Flux::toast(
            heading: 'Gespeichert',
            text: $isCreating ? 'FAQ wurde erstellt.' : 'FAQ wurde aktualisiert.',
            variant: 'success',
        );
    }

    public function modalName(): string
    {
        return 'admin-faq-editor';
    }

    protected function prepareValidation(): void
    {
        $this->title = trim($this->title);
        $this->contentMd = trim($this->contentMd);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'title' => trim($this->title),
            'contentMd' => trim($this->contentMd),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldLabels(): array
    {
        return [
            'title' => 'Frage',
            'contentMd' => 'Antwort',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function logContext(): array
    {
        return ['faq_id' => $this->faqId];
    }

    protected function fillFromFaq(Faq $faq): void
    {
        $this->title = $faq->title;
        $this->contentMd = $faq->content_md;
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::guard('web')->check(), 403);
    }
}

<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SyncDonationEventFaqsAction;
use App\Models\DonationEvent;
use App\Models\Faq;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdminDonationEventFaqsForm extends Component
{
    /**
     * @var array<int, array{id: int, title: string, excerpt: string, attached: bool, was_attached: bool, group: string, sort_order: int, is_published: bool}>
     */
    public array $faqRows = [];

    public DonationEvent $donationEvent;

    public bool $hasUnsavedChanges = false;

    public function mount(DonationEvent $donationEvent): void
    {
        $this->donationEvent = $donationEvent;
        $this->loadFaqRows();
    }

    public function render(): Factory|View
    {
        return view('components.admin-donation-event-faqs-form');
    }

    public function save(SyncDonationEventFaqsAction $syncDonationEventFaqs): void
    {
        abort_unless(Auth::check(), 403);

        try {
            $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        } catch (ValidationException $validationException) {
            $this->hasUnsavedChanges = true;

            throw $validationException;
        }

        $syncDonationEventFaqs($this->donationEvent, $validated['faqRows']);
        $this->loadFaqRows();
        $this->hasUnsavedChanges = false;

        Flux::toast(
            heading: 'Gespeichert',
            text: 'FAQs wurden aktualisiert.',
            variant: 'success',
        );
    }

    public function attachFaq(int $index): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless(isset($this->faqRows[$index]), 404);

        $this->faqRows[$index]['attached'] = true;
        $this->faqRows[$index]['is_published'] = false;
        $this->hasUnsavedChanges = true;
    }

    public function detachFaq(int $index): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless(isset($this->faqRows[$index]), 404);

        $this->faqRows[$index]['attached'] = false;
        $this->hasUnsavedChanges = true;
    }

    /**
     * German labels for the FAQ groups rendered on the public questions-and-answers page.
     *
     * @return array<string, string>
     */
    public function groupOptions(): array
    {
        return [
            'general' => 'Allgemein',
            'athletes' => 'Sportler:innen',
            'donors' => 'Spender:innen',
            'background' => 'Hintergründe',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'faqRows' => ['array'],
            'faqRows.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists(Faq::class, 'id')],
            'faqRows.*.attached' => ['required', 'boolean'],
        ];

        foreach ($this->faqRows as $index => $faqRow) {
            if (! $faqRow['attached']) {
                continue;
            }

            $rules[sprintf('faqRows.%d.group', $index)] = ['required', 'string', Rule::in(array_keys($this->groupOptions()))];
            $rules[sprintf('faqRows.%d.sort_order', $index)] = ['required', 'integer', 'min:0'];
            $rules[sprintf('faqRows.%d.is_published', $index)] = ['required', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->faqRows as $index => $faqRow) {
            $attributes[sprintf('faqRows.%d.group', $index)] = 'Gruppe für '.$faqRow['title'];
            $attributes[sprintf('faqRows.%d.sort_order', $index)] = 'Reihenfolge für '.$faqRow['title'];
            $attributes[sprintf('faqRows.%d.is_published', $index)] = 'Veröffentlichung für '.$faqRow['title'];
        }

        return $attributes;
    }

    protected function loadFaqRows(): void
    {
        $assignedFaqs = $this->donationEvent->faqs()->get()->keyBy('id');

        $nextSortOrder = ((int) $assignedFaqs->max(function (Faq $faq): int {
            $pivot = $faq->getRelation('pivot');

            return $pivot instanceof Pivot ? (int) $pivot->getAttribute('sort_order') : 0;
        })) + 10;

        $this->faqRows = Faq::query()
            ->orderBy('title')
            ->get()
            ->values()
            ->map(function (Faq $faq) use ($assignedFaqs, $nextSortOrder): array {
                $assignedFaq = $assignedFaqs->get($faq->id);
                $pivot = $assignedFaq instanceof Faq ? $assignedFaq->getRelation('pivot') : null;

                return [
                    'id' => $faq->id,
                    'title' => $faq->title,
                    'excerpt' => self::excerpt($faq->content_md),
                    'attached' => $assignedFaq instanceof Faq,
                    'was_attached' => $assignedFaq instanceof Faq,
                    'group' => (string) ($pivot instanceof Pivot ? $pivot->getAttribute('group') : 'general'),
                    'sort_order' => $pivot instanceof Pivot
                        ? (int) $pivot->getAttribute('sort_order')
                        : $nextSortOrder,
                    'is_published' => $pivot instanceof Pivot && (bool) $pivot->getAttribute('is_published'),
                ];
            })
            ->sort(function (array $left, array $right): int {
                if ($left['attached'] !== $right['attached']) {
                    return $left['attached'] ? -1 : 1;
                }

                if ($left['attached']) {
                    $groupCompare = strcasecmp($left['group'], $right['group']);

                    if ($groupCompare !== 0) {
                        return $groupCompare;
                    }

                    if ($left['sort_order'] !== $right['sort_order']) {
                        return $left['sort_order'] <=> $right['sort_order'];
                    }
                }

                return strcasecmp($left['title'], $right['title']);
            })
            ->values()
            ->all();
    }

    /**
     * Plain-text preview of the markdown answer, rendered like the public page.
     */
    public static function excerpt(string $contentMd): string
    {
        return str(strip_tags((string) Str::markdown($contentMd, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ])))
            ->squish()
            ->limit(120)
            ->toString();
    }
}

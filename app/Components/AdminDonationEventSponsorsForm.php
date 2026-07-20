<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SyncDonationEventSponsorsAction;
use App\Models\DonationEvent;
use App\Models\Sponsor;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdminDonationEventSponsorsForm extends Component
{
    /**
     * @var array<int, array{id: int, name: string, attached: bool, was_attached: bool, size: string, contribution_text: string, sort_order: int, is_published: bool}>
     */
    public array $sponsorRows = [];

    public DonationEvent $donationEvent;

    public bool $hasUnsavedChanges = false;

    public function mount(DonationEvent $donationEvent): void
    {
        $this->donationEvent = $donationEvent;
        $this->loadSponsorRows();
    }

    public function render(): Factory|View
    {
        return view('components.admin-donation-event-sponsors-form');
    }

    public function save(SyncDonationEventSponsorsAction $syncDonationEventSponsors): void
    {
        abort_unless(Auth::check(), 403);

        try {
            $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        } catch (ValidationException $validationException) {
            $this->hasUnsavedChanges = true;

            throw $validationException;
        }

        $syncDonationEventSponsors($this->donationEvent, $validated['sponsorRows']);
        $this->loadSponsorRows();
        $this->hasUnsavedChanges = false;

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Sponsor:innen wurden aktualisiert.',
            variant: 'success',
        );
    }

    public function attachSponsor(int $index): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless(isset($this->sponsorRows[$index]), 404);

        $this->sponsorRows[$index]['attached'] = true;
        $this->sponsorRows[$index]['is_published'] = false;
        $this->hasUnsavedChanges = true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'sponsorRows' => ['array'],
            'sponsorRows.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists(Sponsor::class, 'id')],
            'sponsorRows.*.attached' => ['required', 'boolean'],
        ];

        foreach ($this->sponsorRows as $index => $sponsorRow) {
            if (! $sponsorRow['attached']) {
                continue;
            }

            $rules[sprintf('sponsorRows.%d.size', $index)] = ['required', 'string', Rule::in(['small', 'medium', 'large'])];
            $rules[sprintf('sponsorRows.%d.contribution_text', $index)] = ['required', 'string'];
            $rules[sprintf('sponsorRows.%d.sort_order', $index)] = ['required', 'integer', 'min:0'];
            $rules[sprintf('sponsorRows.%d.is_published', $index)] = ['required', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->sponsorRows as $index => $sponsorRow) {
            $attributes[sprintf('sponsorRows.%d.size', $index)] = 'Grösse für '.$sponsorRow['name'];
            $attributes[sprintf('sponsorRows.%d.contribution_text', $index)] = 'Beitrag für '.$sponsorRow['name'];
            $attributes[sprintf('sponsorRows.%d.sort_order', $index)] = 'Reihenfolge für '.$sponsorRow['name'];
            $attributes[sprintf('sponsorRows.%d.is_published', $index)] = 'Veröffentlichung für '.$sponsorRow['name'];
        }

        return $attributes;
    }

    protected function loadSponsorRows(): void
    {
        $assignedSponsors = $this->donationEvent->sponsors()->get()->keyBy('id');

        $nextSortOrder = ((int) $assignedSponsors->max(function (Sponsor $sponsor): int {
            $pivot = $sponsor->getRelation('pivot');

            return $pivot instanceof Pivot ? (int) $pivot->getAttribute('sort_order') : 0;
        })) + 10;

        $this->sponsorRows = Sponsor::query()
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Sponsor $sponsor) use ($assignedSponsors, $nextSortOrder): array {
                $assignedSponsor = $assignedSponsors->get($sponsor->id);
                $pivot = $assignedSponsor instanceof Sponsor ? $assignedSponsor->getRelation('pivot') : null;

                return [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'attached' => $assignedSponsor instanceof Sponsor,
                    'was_attached' => $assignedSponsor instanceof Sponsor,
                    'size' => (string) ($pivot instanceof Pivot ? $pivot->getAttribute('size') : 'medium'),
                    'contribution_text' => (string) ($pivot instanceof Pivot ? $pivot->getAttribute('contribution_text') : ''),
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

                if ($left['attached'] && $left['sort_order'] !== $right['sort_order']) {
                    return $left['sort_order'] <=> $right['sort_order'];
                }

                return strcasecmp($left['name'], $right['name']);
            })
            ->values()
            ->all();
    }
}

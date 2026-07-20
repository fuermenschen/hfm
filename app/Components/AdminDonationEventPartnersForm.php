<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SyncDonationEventPartnersAction;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\Partner;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AdminDonationEventPartnersForm extends Component
{
    public DonationEvent $donationEvent;

    /**
     * @var array<int, array{id: int, name: string, attached: bool, was_attached: bool, sort_order: int, is_published: bool, is_locked: bool, registration_count: int}>
     */
    public array $partnerRows = [];

    public bool $hasUnsavedChanges = false;

    public function mount(DonationEvent $donationEvent): void
    {
        $this->donationEvent = $donationEvent;
        $this->loadPartnerRows();
    }

    public function render(): Factory|View
    {
        return view('components.admin-donation-event-partners-form');
    }

    public function save(SyncDonationEventPartnersAction $syncDonationEventPartners): void
    {
        abort_unless(Auth::check(), 403);

        try {
            $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        } catch (ValidationException $validationException) {
            $this->hasUnsavedChanges = true;

            throw $validationException;
        }

        $syncDonationEventPartners($this->donationEvent, $validated['partnerRows']);
        $this->loadPartnerRows();
        $this->hasUnsavedChanges = false;

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Partner:innen wurden aktualisiert.',
            variant: 'success',
        );
    }

    public function attachPartner(int $index): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless(isset($this->partnerRows[$index]), 404);

        $this->partnerRows[$index]['attached'] = true;
        $this->partnerRows[$index]['is_published'] = false;
        $this->hasUnsavedChanges = true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'partnerRows' => ['array'],
            'partnerRows.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists(Partner::class, 'id')],
            'partnerRows.*.attached' => ['required', 'boolean'],
        ];

        foreach ($this->partnerRows as $index => $partnerRow) {
            if (! $partnerRow['attached'] && ! $partnerRow['is_locked']) {
                continue;
            }

            $rules[sprintf('partnerRows.%d.sort_order', $index)] = ['required', 'integer', 'min:0'];
            $rules[sprintf('partnerRows.%d.is_published', $index)] = ['required', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->partnerRows as $index => $partnerRow) {
            $attributes[sprintf('partnerRows.%d.sort_order', $index)] = 'Reihenfolge für '.$partnerRow['name'];
            $attributes[sprintf('partnerRows.%d.is_published', $index)] = 'Veröffentlichung für '.$partnerRow['name'];
        }

        return $attributes;
    }

    protected function loadPartnerRows(): void
    {
        $assignedPartners = $this->donationEvent->partners()->get()->keyBy('id');
        $registrationCounts = AthleteRegistration::query()
            ->whereBelongsTo($this->donationEvent)
            ->whereNotNull('partner_id')
            ->selectRaw('partner_id, COUNT(*) as aggregate')
            ->groupBy('partner_id')
            ->pluck('aggregate', 'partner_id');

        $nextSortOrder = ((int) $assignedPartners->max(function (Partner $partner): int {
            $pivot = $partner->getRelation('pivot');

            return $pivot instanceof Pivot ? (int) $pivot->getAttribute('sort_order') : 0;
        })) + 10;

        $this->partnerRows = Partner::query()
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Partner $partner) use ($assignedPartners, $registrationCounts, $nextSortOrder): array {
                $assignedPartner = $assignedPartners->get($partner->id);
                $pivot = $assignedPartner instanceof Partner ? $assignedPartner->getRelation('pivot') : null;
                $registrationCount = (int) $registrationCounts->get($partner->id, 0);

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'attached' => $assignedPartner instanceof Partner || $registrationCount > 0,
                    'was_attached' => $assignedPartner instanceof Partner || $registrationCount > 0,
                    'sort_order' => $pivot instanceof Pivot
                        ? (int) $pivot->getAttribute('sort_order')
                        : $nextSortOrder,
                    'is_published' => $pivot instanceof Pivot && (bool) $pivot->getAttribute('is_published'),
                    'is_locked' => $registrationCount > 0,
                    'registration_count' => $registrationCount,
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

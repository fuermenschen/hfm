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
use Livewire\Component;

class AdminDonationEventSponsorsForm extends Component
{
    /**
     * @var array<int, array{id: int, name: string, attached: bool, size: string, contribution_text: string, sort_order: int, is_published: bool}>
     */
    public array $sponsorRows = [];

    public DonationEvent $donationEvent;

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

        $validated = $this->validate($this->rules());

        $syncDonationEventSponsors($this->donationEvent, $validated['sponsorRows']);
        $this->loadSponsorRows();

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Sponsor:innen wurden aktualisiert.',
            variant: 'success',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'sponsorRows' => ['array'],
            'sponsorRows.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists(Sponsor::class, 'id')],
            'sponsorRows.*.attached' => ['required', 'boolean'],
            'sponsorRows.*.size' => ['required', 'string', Rule::in(['small', 'medium', 'large'])],
            'sponsorRows.*.contribution_text' => ['nullable', 'required_if:sponsorRows.*.attached,true', 'string'],
            'sponsorRows.*.sort_order' => ['required', 'integer', 'min:0'],
            'sponsorRows.*.is_published' => ['required', 'boolean'],
        ];
    }

    protected function loadSponsorRows(): void
    {
        $assignedSponsors = $this->donationEvent->sponsors()->get()->keyBy('id');

        $this->sponsorRows = Sponsor::query()
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Sponsor $sponsor, int $index) use ($assignedSponsors): array {
                $assignedSponsor = $assignedSponsors->get($sponsor->id);
                $pivot = $assignedSponsor instanceof Sponsor ? $assignedSponsor->getRelation('pivot') : null;

                return [
                    'id' => $sponsor->id,
                    'name' => $sponsor->name,
                    'attached' => $assignedSponsor instanceof Sponsor,
                    'size' => (string) ($pivot instanceof Pivot ? $pivot->getAttribute('size') : 'medium'),
                    'contribution_text' => (string) ($pivot instanceof Pivot ? $pivot->getAttribute('contribution_text') : ''),
                    'sort_order' => $pivot instanceof Pivot
                        ? (int) $pivot->getAttribute('sort_order')
                        : ($index + 1) * 10,
                    'is_published' => $pivot instanceof Pivot
                        ? (bool) $pivot->getAttribute('is_published')
                        : true,
                ];
            })
            ->all();
    }
}

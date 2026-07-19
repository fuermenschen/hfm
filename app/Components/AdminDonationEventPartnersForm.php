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
use Livewire\Component;

class AdminDonationEventPartnersForm extends Component
{
    public DonationEvent $donationEvent;

    /**
     * @var array<int, array{id: int, name: string, attached: bool, sort_order: int, is_published: bool, is_locked: bool, registration_count: int}>
     */
    public array $partnerRows = [];

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

        $validated = $this->validate($this->rules());

        $syncDonationEventPartners($this->donationEvent, $validated['partnerRows']);
        $this->loadPartnerRows();

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Partner:innen wurden aktualisiert.',
            variant: 'success',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'partnerRows' => ['array'],
            'partnerRows.*.id' => ['required', 'integer', 'distinct:strict', Rule::exists(Partner::class, 'id')],
            'partnerRows.*.attached' => ['required', 'boolean'],
            'partnerRows.*.sort_order' => ['required', 'integer', 'min:0'],
            'partnerRows.*.is_published' => ['required', 'boolean'],
        ];
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

        $this->partnerRows = Partner::query()
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (Partner $partner, int $index) use ($assignedPartners, $registrationCounts): array {
                $assignedPartner = $assignedPartners->get($partner->id);
                $pivot = $assignedPartner instanceof Partner ? $assignedPartner->getRelation('pivot') : null;
                $registrationCount = (int) $registrationCounts->get($partner->id, 0);

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'attached' => $assignedPartner instanceof Partner || $registrationCount > 0,
                    'sort_order' => $pivot instanceof Pivot
                        ? (int) $pivot->getAttribute('sort_order')
                        : ($index + 1) * 10,
                    'is_published' => $pivot instanceof Pivot
                        ? (bool) $pivot->getAttribute('is_published')
                        : true,
                    'is_locked' => $registrationCount > 0,
                    'registration_count' => $registrationCount,
                ];
            })
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\ConfirmAthleteRegistrationAction;
use App\Actions\ConfirmDonationAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PortalConfirmationButton extends Component
{
    #[Locked]
    public string $type;

    #[Locked]
    public int $recordId;

    public function mount(string $type, int $recordId): void
    {
        abort_unless(in_array($type, ['athlete', 'donation'], true), 404);

        $this->type = $type;
        $this->recordId = $recordId;
    }

    public function confirm(
        ConfirmAthleteRegistrationAction $confirmAthleteRegistration,
        ConfirmDonationAction $confirmDonation,
    ): void {
        $externalUser = auth()->guard('external')->user();
        throw_if(! $externalUser instanceof ExternalUser, AuthorizationException::class);

        if ($this->type === 'athlete') {
            $athleteRegistration = AthleteRegistration::query()->findOrFail($this->recordId);
            $confirmAthleteRegistration($athleteRegistration, $externalUser);
            $athleteRegistration->loadMissing('donationEvent');

            session()->flash('success', 'Deine Registrierung als Sportler:in ist bestätigt.');
            $this->redirectRoute(
                'portal.participations',
                $athleteRegistration->donationEvent->is_published ? ['anlass' => $athleteRegistration->donationEvent->slug] : [],
                navigate: true,
            );

            return;
        }

        $donation = Donation::query()->findOrFail($this->recordId);
        $confirmDonation($donation, $externalUser);
        $donation->loadMissing('athleteRegistration.donationEvent');

        session()->flash('success', 'Deine Spende ist bestätigt.');
        $this->redirectRoute(
            'portal.donations',
            $donation->athleteRegistration->donationEvent->is_published ? ['anlass' => $donation->athleteRegistration->donationEvent->slug] : [],
            navigate: true,
        );
    }

    public function render(): Factory|View
    {
        return view('components.portal-confirmation-button');
    }
}

<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\Donation;
use App\Models\Donor;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DonorDetails extends Component
{
    #[Locked]
    public Donor $donor;

    public Collection $donations;

    public function mount($login_token, $donation_id = null): void
    {
        $this->donor = Donor::query()->where('login_token', $login_token)->with('donations.athlete')->firstOrFail();

        // check if the donation is not verified yet
        if ($donation_id) {
            $donation = $this->donor->donations->where('id', $donation_id)->first();
            if (! $donation) {
                // show an error message
                Flux::toast(
                    heading: 'Spende nicht gefunden!',
                    text: 'Die Spende konnte nicht gefunden werden. Bitte überprüfe den Link.',
                    variant: 'danger',
                );
            } elseif (! $donation->verified) {
                // mark the donation as verified
                $donation->verified = true;
                $donation->save();

                // show a success message
                Flux::toast(
                    heading: 'Spende bestätigt!',
                    text: 'Deine Spende für '.$donation->athlete->privacy_name.' wurde bestätigt. Vielen Dank!',
                    variant: 'success',
                );
            }
        }

        $donations = Donation::query()->where('donor_id', $this->donor->id)->with('athlete')->get();
        $this->donations = $donations->map(function ($donation): array {
            return [
                'athlete' => $donation->athlete->privacy_name,
                'public_id' => $donation->athlete->public_id_string,
                'amount_per_round' => $donation->amount_per_round,
                'amount_min' => $donation->amount_min,
                'amount_max' => $donation->amount_max,
                'rounds_estimated' => $donation->athlete->rounds_estimated,
            ];
        });
    }

    public function render(): Factory|View
    {
        return view('components.donor-details');
    }
}

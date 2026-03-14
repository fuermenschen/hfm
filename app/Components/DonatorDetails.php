<?php

namespace App\Components;

use App\Models\Donation;
use App\Models\Donator;
use Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DonatorDetails extends Component
{
    #[Locked]
    public Donator $donator;

    public Collection $donations;

    public function mount($login_token, $donation_id = null)
    {
        // try to find the corresponding donator
        $this->donator = Donator::query()->where('login_token', $login_token)->with('donations.athlete')->firstOrFail();

        // check if the donation is not verified yet
        if ($donation_id) {
            $donation = $this->donator->donations->where('id', $donation_id)->first();
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

        $donations = Donation::where('donator_id', $this->donator->id)->with('athlete')->get();
        $this->donations = $donations->map(function ($donation) {
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

    public function render()
    {
        return view('components.donator-details');
    }
}

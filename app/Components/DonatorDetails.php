<?php

namespace App\Components;

use App\Models\Donation;
use App\Models\Donator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;
use WireUi\Traits\Actions;

class DonatorDetails extends Component
{
    use Actions;

    #[Locked]
    public array $donator;

    public Collection $donations;

    public function mount($login_token, $donation_id = null)
    {
        // try to find the corresponding donator
        $donator = Donator::where('login_token', $login_token)->firstOrFail();

        // check if the donation is not verified yet
        if ($donation_id) {
            $donation = $donator->donations()->where('id', $donation_id)->firstOrFail();
            if (!$donation->verified) {
                // mark the donation as verified
                $donation->verified = true;
                $donation->save();

                // show a success message
                $this->dialog()->success(
                    $title = 'Spende bestätigt!',
                    $message = 'Deine Spende für ' . $donation->athlete->privacy_name . ' wurde bestätigt. Vielen Dank!'
                );
            }
        }

        $this->donator = [
            'first_name' => $donator->first_name,
        ];

        $donations = Donation::where('donator_id', $donator->id)->with('athlete')->get();
        $this->donations = $donations->map(function ($donation) {
            return [
                'athlete' => $donation->athlete->privacy_name,
                'public_id' => $donation->athlete->public_id_string,
                'amount_per_round' => $donation->amount_per_round,
                'amount_min' => $donation->amount_min,
                'amount_max' => $donation->amount_max,
                'rounds_estimated' => $donation->athlete->rounds_estimated
            ];
        });
    }

    public function render()
    {
        return view('components.donator-details');
    }

}

<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;
use WireUi\Traits\Actions;

class AthleteDetails extends Component
{
    use Actions;

    #[Locked]
    public Athlete $athlete;

    public Collection $donations;

    public function mount($login_token)
    {
        // try to find the corresponding athlete
        $athlete = Athlete::where('login_token', $login_token)->firstOrFail();

        // check if the athlete is not verified yet
        if (!$athlete->verified) {
            // mark the athlete as verified
            $athlete->verified = true;
            $athlete->save();

            // show a success message
            $this->dialog()->success(
                $title = 'Anmeldung bestätigt!',
                $message = 'Deine Anmeldung wurde bestätigt. Ab sofort können Spender:innen dich bei der Anmeldung auswählen'
            );
        }

        $this->athlete = $athlete;
        $this->donations = Donation::where('athlete_id', $athlete->id)->with('donator')->get();
    }

    public function render()
    {
        return view('components.athlete-details');
    }

}

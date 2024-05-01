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

        // check if the login token is still valid
        if ($athlete->login_token_expires_at < now()) {

            $athlete->login_token = $athlete->newTokenAndNotify();

            // show an error message
            $this->dialog(
                [
                    'icon' => 'info',
                    'title' => 'Anmeldung abgelaufen!',
                    'description' => 'Dein Anmeldelink ist abgelaufen. Du hast einen neuen Link per Mail bekommen.',
                    "onClose" => [
                        "method" => "redirectHelper",
                    ],
                ]);
        }
        $this->athlete = $athlete;
        $this->donations = Donation::where('athlete_id', $athlete->id)->with('donator')->get();
    }

    public function render()
    {
        return view('components.athlete-details');
    }

    public function redirectHelper(): void
    {
        $this->redirect(route("home"), navigate: true);
    }
}

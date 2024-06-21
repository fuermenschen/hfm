<?php

namespace App\Components;

require './../vendor/autoload.php';

use App\Models\Athlete;
use App\Models\Donation;
use Illuminate\Support\Collection;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Locked;
use Livewire\Component;
use WireUi\Traits\Actions;

class AthleteDetails extends Component
{
    use Actions;

    #[Locked]
    public array $athlete;

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

        $this->athlete = [
            'first_name' => $athlete->first_name,
            'privacy_name' => $athlete->privacy_name,
            'public_id_string' => $athlete->public_id_string,
        ];
        $donations = Donation::where('athlete_id', $athlete->id)->with('donator')->get();
        $this->donations = $donations->map(function ($donation) {
            return [
                'donator' => $donation->donator->privacy_name,
                'amount_per_round' => $donation->amount_per_round,
                'amount_min' => $donation->amount_min,
                'amount_max' => $donation->amount_max,
                'verified' => $donation->verified,
            ];
        });
    }

    public function render()
    {
        return view('components.athlete-details');
    }

    public function downloadPersonalizedImage()
    {
        $image = ImageManager::gd()->read('./../resources/image_templates/templ1.jpg');

        // define the position and text
        $x = 539;
        $y = 1561;
        $text = $this->athlete['privacy_name'] . " (" . $this->athlete['public_id_string'] . ")";

        // add the text to the image
        $image->text($text, $x, $y, function ($font) {
            $font->file('./../resources/fonts/darkmode_on_medium.otf');
            $font->size(55);
            $font->color('#f8fafc');
            $font->align('center');
            $font->valign('middle');
        });

        // create filename
        $filename = $this->athlete['public_id_string'] . '.jpg';

        $filepath = './../public/temp/' . $filename;

        $image->save($filepath);

        return response()->download($filepath)->deleteFileAfterSend(true);

    }

}

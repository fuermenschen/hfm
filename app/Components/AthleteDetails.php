<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donation;
use Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AthleteDetails extends Component
{
    #[Locked]
    public array $athlete;

    public Collection $donations;

    public function mount($login_token)
    {
        // try to find the corresponding athlete
        $athlete = Athlete::where('login_token', $login_token)->firstOrFail();

        // check if the athlete is not verified yet
        if (! $athlete->verified) {
            // mark the athlete as verified
            $athlete->verified = true;
            $athlete->save();

            // show a success message
            Flux::toast(
                heading: 'Anmeldung bestätigt!',
                text: 'Deine Anmeldung wurde bestätigt. Ab sofort können Spender:innen dich bei der Anmeldung auswählen',
                variant: 'success',
            );
        }

        $this->athlete = [
            'first_name' => $athlete->first_name,
            'privacy_name' => $athlete->privacy_name,
            'public_id_string' => $athlete->public_id_string,
        ];
        $donations = Donation::where('athlete_id', $athlete->id)->with('donor')->get();
        $this->donations = $donations->map(function ($donation) {
            return [
                'donor' => $donation->donor->privacy_name,
                'amount_per_round' => $donation->amount_per_round,
                'amount_min' => $donation->amount_min,
                'amount_max' => $donation->amount_max,
                'verified' => $donation->verified,
                'comment' => $donation->comment,
            ];
        });
    }

    public function render()
    {
        return view('components.athlete-details');
    }

    public function downloadStorySingleDark()
    {
        $image = ImageManager::usingDriver(Driver::class)->decodePath('./../resources/image_templates/story_single_dark.jpg');

        // define the position and text
        $x = 539;
        $y = 1561;
        $text = $this->athlete['privacy_name'].' ('.$this->athlete['public_id_string'].')';

        // add the text to the image
        $image->text($text, $x, $y, function ($font) {
            $font->file('./../resources/fonts/darkmode_on_medium.otf');
            $font->size(55);
            $font->color('#f8fafc');
            $font->align('center');
            $font->valign('middle');
        });

        // create filename
        $filename = 'story_single_dark_'.$this->athlete['public_id_string'].'_'.Str::random(5).'.jpg';

        $filepath = './../storage/temp/'.$filename;

        $image->save($filepath);

        return response()->download($filepath)->deleteFileAfterSend(true);

    }

    public function downloadStorySingleLight()
    {
        $image = ImageManager::usingDriver(Driver::class)->decodePath('./../resources/image_templates/story_single_light.jpg');

        // define the position and text
        $x = 539;
        $y = 1561;
        $text = $this->athlete['privacy_name'].' ('.$this->athlete['public_id_string'].')';

        // add the text to the image
        $image->text($text, $x, $y, function ($font) {
            $font->file('./../resources/fonts/darkmode_on_medium.otf');
            $font->size(55);
            $font->color('#f8fafc');
            $font->align('center');
            $font->valign('middle');
        });

        // create filename
        $filename = 'story_single_light_'.$this->athlete['public_id_string'].'_'.Str::random(5).'.jpg';

        $filepath = './../storage/temp/'.$filename;

        $image->save($filepath);

        return response()->download($filepath)->deleteFileAfterSend(true);

    }
}

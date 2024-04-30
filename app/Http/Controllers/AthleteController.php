<?php

namespace App\Http\Controllers;

use App\Models\Athlete;

class AthleteController extends Controller
{
    public function show($login_token)
    {
        $athlete = Athlete::where('login_token', $login_token)->firstOrFail();

        // check if the athlete already verified their email
        if (!$athlete->isVerified) {
            $athlete->markEmailAsVerified();
        }

        // TODO: a LOT!

        return view("pages.athlete.show", [
            'athlete' => Athlete::where('login_token', $login_token)->firstOrFail(),
        ]);
    }
}

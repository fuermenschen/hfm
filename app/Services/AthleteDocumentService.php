<?php

namespace App\Services;

use App\Models\Athlete;
use Barryvdh\DomPDF\PDF;

class AthleteDocumentService
{
    /**
     * @return array{pdf:PDF,filename:string}
     */
    public function buildWelcomeLetter(Athlete $athlete): array
    {
        $filename = $athlete->first_name.'_'.$athlete->last_name.'_Willkommensbrief.pdf';
        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper')->loadView('printables.athlete_welcome_letter', compact('athlete'))
            ->setPaper('a4', 'portrait');

        return [
            'pdf' => $pdf,
            'filename' => $filename,
        ];
    }

    /**
     * @return array{pdf:PDF,filename:string}
     */
    public function buildPersonalizedFlyer(Athlete $athlete): array
    {
        $filename = $athlete->first_name.'_'.$athlete->last_name.'_Flyer.pdf';
        /** @var PDF $pdf */
        $pdf = app('dompdf.wrapper')->loadView('printables.athlete_personalized_flyer', compact('athlete'))
            ->setPaper('a5', 'portrait');

        return [
            'pdf' => $pdf,
            'filename' => $filename,
        ];
    }
}

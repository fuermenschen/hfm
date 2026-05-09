<?php

declare(strict_types=1);

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
        $pdf = resolve('dompdf.wrapper')->loadView('printables.athlete_welcome_letter', ['athlete' => $athlete])
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
        $pdf = resolve('dompdf.wrapper')->loadView('printables.athlete_personalized_flyer', ['athlete' => $athlete])
            ->setPaper('a5', 'portrait');

        return [
            'pdf' => $pdf,
            'filename' => $filename,
        ];
    }
}

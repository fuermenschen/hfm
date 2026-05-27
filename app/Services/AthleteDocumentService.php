<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalUser;
use Barryvdh\DomPDF\PDF;

class AthleteDocumentService
{
    /**
     * @return array{pdf:PDF,filename:string}
     */
    // Service currently has no production call site; kept for planned relaunch of athlete documents.
    // TODO(dead-code): Remove ignore when welcome letter flow is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function buildWelcomeLetter(ExternalUser $athlete): array
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
    // Service currently has no production call site; kept for planned relaunch of athlete documents.
    // TODO(dead-code): Remove ignore when personalized flyer flow is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function buildPersonalizedFlyer(ExternalUser $athlete): array
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

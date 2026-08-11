<?php

declare(strict_types=1);

namespace App\Enums;

enum AthleteDocumentType: string
{
    case WelcomeLetter = 'welcome-letter';
    case PersonalizedFlyer = 'personalized-flyer';

    public function view(): string
    {
        return match ($this) {
            self::WelcomeLetter => 'printables.athlete_welcome_letter',
            self::PersonalizedFlyer => 'printables.athlete_personalized_flyer',
        };
    }

    public function paper(): string
    {
        return match ($this) {
            self::WelcomeLetter => 'a4',
            self::PersonalizedFlyer => 'a5',
        };
    }

    public function filenameSuffix(): string
    {
        return match ($this) {
            self::WelcomeLetter => 'Willkommensbrief',
            self::PersonalizedFlyer => 'Personalisierter_Flyer',
        };
    }

    public function archiveFilename(): string
    {
        return match ($this) {
            self::WelcomeLetter => 'Willkommensbriefe',
            self::PersonalizedFlyer => 'Personalisierte_Flyer',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum EventState: string
{
    case NotStarted = 'not_started';
    case Running = 'running';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Nicht gestartet',
            self::Running => 'Läuft',
            self::Finished => 'Fertig',
        };
    }
}

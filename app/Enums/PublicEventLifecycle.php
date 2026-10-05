<?php

declare(strict_types=1);

namespace App\Enums;

enum PublicEventLifecycle: string
{
    case Upcoming = 'upcoming';
    case Live = 'live';
    case Completed = 'completed';
}

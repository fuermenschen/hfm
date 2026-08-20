<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupMembershipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
}

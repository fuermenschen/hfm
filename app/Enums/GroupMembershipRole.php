<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupMembershipRole: string
{
    case Member = 'member';
    case Admin = 'admin';
}

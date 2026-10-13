<?php

declare(strict_types=1);

namespace App\Enums;

enum DonorPaymentVerification: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';
    case Unclear = 'unclear';
}

<?php

namespace App\Jobs;

use App\Services\Infomaniak\InfomaniakNewsletterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterNewsletterSubscriber implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $firstName, public string $email) {}

    public function handle(InfomaniakNewsletterService $newsletterService): void
    {
        $newsletterService->registerSubscriber($this->firstName, $this->email);
    }
}

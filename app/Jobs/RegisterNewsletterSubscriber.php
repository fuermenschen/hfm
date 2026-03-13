<?php

namespace App\Jobs;

use App\Notifications\NewsletterRegistrationStatusNotification;
use App\Services\Infomaniak\InfomaniakNewsletterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RegisterNewsletterSubscriber implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $firstName, public string $email) {}

    public function handle(InfomaniakNewsletterService $newsletterService): void
    {
        try {
            $alreadyRegistered = $newsletterService->registerSubscriber($this->firstName, $this->email);

            Notification::route('mail', $this->email)
                ->notify(new NewsletterRegistrationStatusNotification($this->firstName, $alreadyRegistered));
        } catch (Throwable $exception) {
            Log::error('Newsletter registration API call failed.', [
                'email' => $this->email,
                'first_name' => $this->firstName,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

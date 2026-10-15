<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Services\AthleteStoryImageService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\UniqueFor;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

#[Timeout(60)]
#[UniqueFor(86400)]
class WarmAthleteStoryImage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public int $registrationId, public StoryImageVariant $variant)
    {
        $this->onQueue('maintenance');
    }

    public function uniqueId(): string
    {
        return $this->registrationId.':'.$this->variant->value;
    }

    public function handle(AthleteStoryImageService $images): void
    {
        $registration = AthleteRegistration::query()->with(['donationEvent', 'externalUser'])
            ->whereKey($this->registrationId)->where('verified', true)->whereHas('externalUser')->first();

        if ($registration === null || ! $registration->donationEvent->is_published
            || $registration->donationEvent->ends_at === null || $registration->donationEvent->hasEnded()) {
            return;
        }

        try {
            $images->build($registration, $this->variant);
        } catch (ServiceUnavailableHttpException) {
            $this->release(120);
        }
    }
}

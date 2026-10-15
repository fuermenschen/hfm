<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\UniqueFor;

#[Timeout(60)]
#[UniqueFor(86400)]
class DispatchStoryImageWarming implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public int $afterId = 0, public ?int $untilId = null)
    {
        $this->onQueue('maintenance');
    }

    public function uniqueId(): string
    {
        return (string) $this->afterId;
    }

    public function handle(): void
    {
        $untilId = $this->untilId ?? (int) AthleteRegistration::query()->max('id');
        $registrations = AthleteRegistration::query()
            ->where('id', '>', $this->afterId)->where('id', '<=', $untilId)
            ->where('verified', true)->whereHas('externalUser')
            ->whereHas('donationEvent', fn (Builder $query): Builder => $query->where('is_published', true)->where('ends_at', '>', now('UTC')))
            ->orderBy('id')->limit(100)->get(['id']);

        foreach ($registrations as $registration) {
            foreach (StoryImageVariant::cases() as $variant) {
                dispatch(new WarmAthleteStoryImage($registration->id, $variant));
            }
        }

        if ($registrations->count() === 100) {
            dispatch(new self($registrations->last()->id, $untilId));
        }
    }
}

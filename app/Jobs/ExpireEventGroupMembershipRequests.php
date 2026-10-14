<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\GroupMembershipStatus;
use App\Models\DonationEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\Facades\DB;

#[Timeout(60)]
#[UniqueFor(3600)]
class ExpireEventGroupMembershipRequests implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DonationEvent::query()->where('ends_at', '<=', now('UTC'))
            ->chunkById(50, function (Collection $events): void {
                foreach ($events as $event) {
                    DB::transaction(function () use ($event): void {
                        $event = DonationEvent::query()->whereKey($event)->lockForUpdate()->first();
                        if ($event === null || $event->ends_at === null || ! $event->hasEnded()) {
                            return;
                        }

                        $event->athleteRegistrations()
                            ->where('group_membership_status', GroupMembershipStatus::Pending->value)
                            ->update([
                                'event_group_id' => null,
                                'group_membership_status' => null,
                                'group_membership_role' => null,
                            ]);
                    });
                }
            });
    }
}

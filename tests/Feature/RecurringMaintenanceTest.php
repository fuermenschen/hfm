<?php

use App\Enums\GroupMembershipStatus;
use App\Enums\StoryImageVariant;
use App\Jobs\DispatchStoryImageWarming;
use App\Jobs\ExpireEventGroupMembershipRequests;
use App\Jobs\WarmAthleteStoryImage;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Services\AthleteStoryImageService;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use function Pest\Laravel\artisan;
use function Pest\Laravel\freezeTime;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

it('clears only ended pending memberships and tolerates repeated cleanup', function (): void {
    travelTo(now('UTC')->setTime(12, 0));
    $ended = DonationEvent::factory()->create(['timezone' => 'Pacific/Auckland', 'ends_at' => now('UTC')]);
    $active = DonationEvent::factory()->create(['ends_at' => now('UTC')->addSecond()]);
    $endedGroup = EventGroup::factory()->forEvent($ended)->create();
    $activeGroup = EventGroup::factory()->forEvent($active)->create();
    $pending = AthleteRegistration::factory()->pendingGroup($endedGroup)->create();
    $member = AthleteRegistration::factory()->acceptedMember($endedGroup)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($endedGroup)->create();
    $future = AthleteRegistration::factory()->pendingGroup($activeGroup)->create();

    (new ExpireEventGroupMembershipRequests)->handle();
    (new ExpireEventGroupMembershipRequests)->handle();

    expect($pending->refresh()->event_group_id)->toBeNull();
    expect($pending->group_membership_status)->toBeNull();
    expect($pending->group_membership_role)->toBeNull();
    expect($member->refresh()->group_membership_status)->toBe(GroupMembershipStatus::Accepted);
    expect($admin->refresh()->group_membership_status)->toBe(GroupMembershipStatus::Accepted);
    expect($future->refresh()->group_membership_status)->toBe(GroupMembershipStatus::Pending);
});

it('dispatches every variant only for eligible registrations and deduplicates a partial retry', function (): void {
    freezeTime();
    $event = DonationEvent::factory()->create();
    $eligible = AthleteRegistration::factory()->forEvent($event)->verified()->create();
    AthleteRegistration::factory()->forEvent($event)->create(['verified' => false]);
    $deleted = AthleteRegistration::factory()->forEvent($event)->verified()->create();
    $deleted->externalUser->delete();
    AthleteRegistration::factory()->verified()->forEvent(DonationEvent::factory()->create(['is_published' => false]))->create();
    AthleteRegistration::factory()->verified()->forEvent(DonationEvent::factory()->create(['ends_at' => now()->subSecond()]))->create();
    Queue::fake([WarmAthleteStoryImage::class, DispatchStoryImageWarming::class]);
    WarmAthleteStoryImage::dispatch($eligible->id, StoryImageVariant::Light);

    (new DispatchStoryImageWarming)->handle();
    (new DispatchStoryImageWarming)->handle();

    Queue::assertPushed(WarmAthleteStoryImage::class, 2);
    foreach (StoryImageVariant::cases() as $variant) {
        Queue::assertPushedOn('maintenance', WarmAthleteStoryImage::class,
            fn (WarmAthleteStoryImage $job): bool => $job->registrationId === $eligible->id && $job->variant === $variant);
    }
    Queue::assertNotPushed(DispatchStoryImageWarming::class);
});

it('pages dispatch and preserves the snapshot boundary on continuation', function (): void {
    freezeTime();
    $event = DonationEvent::factory()->create();
    $registrations = AthleteRegistration::factory()->count(101)->forEvent($event)->verified()->create();
    Queue::fake([WarmAthleteStoryImage::class, DispatchStoryImageWarming::class]);

    (new DispatchStoryImageWarming)->handle();

    Queue::assertPushed(WarmAthleteStoryImage::class, 200);
    Queue::assertPushed(DispatchStoryImageWarming::class,
        fn (DispatchStoryImageWarming $job): bool => $job->afterId === $registrations[99]->id && $job->untilId === $registrations[100]->id);

    AthleteRegistration::factory()->forEvent($event)->verified()->create();
    (new DispatchStoryImageWarming($registrations[99]->id, $registrations[100]->id))->handle();
    Queue::assertPushed(WarmAthleteStoryImage::class, 202);
});

it('rechecks image eligibility when queued work eventually executes', function (string $change): void {
    freezeTime();
    $registration = AthleteRegistration::factory()->verified()->create();
    $job = new WarmAthleteStoryImage($registration->id, StoryImageVariant::Light);
    match ($change) {
        'ended' => $registration->donationEvent->update(['ends_at' => now()->subSecond()]),
        'unpublished' => $registration->donationEvent->update(['is_published' => false]),
        'unverified' => $registration->update(['verified' => false]),
        'deleted user' => $registration->externalUser->delete(),
        'deleted registration' => $registration->delete(),
        default => throw new InvalidArgumentException('Unknown eligibility change.'),
    };
    $images = Mockery::mock(AthleteStoryImageService::class);
    $images->shouldReceive('build')->never();

    $job->handle($images);
})->with(['ended', 'unpublished', 'unverified', 'deleted user', 'deleted registration']);

it('warms both variants and reuses their valid fingerprinted cache entries', function (): void {
    freezeTime();
    $registration = AthleteRegistration::factory()->verified()->create();
    $images = app(AthleteStoryImageService::class);

    foreach (StoryImageVariant::cases() as $variant) {
        (new WarmAthleteStoryImage($registration->id, $variant))->handle($images);
        $logos = (new ReflectionMethod($images, 'partnerLogos'))->invoke($images, $registration->donationEvent, $variant);
        $baseKey = (new ReflectionMethod($images, 'baseCacheKey'))->invoke($images, $registration->donationEvent, $variant, $logos);
        $key = (new ReflectionMethod($images, 'cacheKey'))->invoke($images, $registration, $variant, $baseKey);
        expect(Cache::has($key))->toBeTrue();
        Cache::forever($key, ['contents' => 'already cached', 'filename' => 'cached.jpg']);

        (new WarmAthleteStoryImage($registration->id, $variant))->handle($images);

        expect($images->build($registration, $variant)['contents'])->toBe('already cached');
    }
});

it('drains both queues with configured normal work ahead of maintenance', function (string $normalQueue): void {
    freezeTime();
    config(['queue.default' => 'database', 'queue.connections.database.queue' => $normalQueue]);
    $registration = AthleteRegistration::factory()->verified()->create();
    $images = Mockery::mock(AthleteStoryImageService::class);
    $images->shouldReceive('build')->once()->withArgs(
        fn (AthleteRegistration $athlete, StoryImageVariant $variant): bool => $athlete->is($registration) && $variant === StoryImageVariant::Dark,
    )->andReturn(['contents' => 'image', 'filename' => 'image.jpg']);
    app()->instance(AthleteStoryImageService::class, $images);
    $processedQueues = [];
    Event::listen(JobProcessed::class, function (JobProcessed $event) use (&$processedQueues): void {
        $processedQueues[] = $event->job->getQueue();
    });
    WarmAthleteStoryImage::dispatch($registration->id, StoryImageVariant::Dark);
    ExpireEventGroupMembershipRequests::dispatch();

    $response = get(route('queue-worker'), ['X-API-Key' => config('app.api_key')]);
    expect($response->getStatusCode())->toBe(200, $response->getContent());

    expect($processedQueues)->toBe([$normalQueue, 'maintenance']);
    expect(Queue::size($normalQueue))->toBe(0);
    expect(Queue::size('maintenance'))->toBe(0);
})->with(['default', 'primary']);

it('does not dispatch maintenance again in the same minute after queued work completes', function (): void {
    travelTo(now('UTC')->next('Sunday')->setTime(1, 0));
    config(['queue.default' => 'database', 'queue.connections.database.queue' => 'default']);
    artisan('schedule:run')->assertSuccessful();
    expect(Queue::size('default'))->toBe(1);
    expect(Queue::size('maintenance'))->toBe(1);
    $response = get(route('queue-worker'), ['X-API-Key' => config('app.api_key')]);
    expect($response->getStatusCode())->toBe(200, $response->getContent());

    artisan('schedule:run')->assertSuccessful();

    expect(Queue::size('default'))->toBe(0);
    expect(Queue::size('maintenance'))->toBe(0);

    travelTo(now('UTC')->addDay());
    artisan('schedule:run')->assertSuccessful();
    expect(Queue::size('default'))->toBe(0);
    expect(Queue::size('maintenance'))->toBe(1);
});

it('delays warming retries until a contended render lock can expire', function (): void {
    freezeTime();
    $registration = AthleteRegistration::factory()->verified()->create();
    $images = Mockery::mock(AthleteStoryImageService::class);
    $images->shouldReceive('build')->once()->andThrow(new ServiceUnavailableHttpException(120));
    $job = (new WarmAthleteStoryImage($registration->id, StoryImageVariant::Light))->withFakeQueueInteractions();

    $job->handle($images);

    $job->assertReleased(120);
    $job->assertNotFailed();
});

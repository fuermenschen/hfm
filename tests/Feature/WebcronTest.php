<?php

use App\Http\Controllers\WebcronController;
use App\Jobs\DispatchStoryImageWarming;
use App\Jobs\ExpireEventGroupMembershipRequests;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schedule;

use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    config(['queue.default' => 'database', 'app.api_key' => 'webcron-test-key']);
});

it('dispatches scheduled work before starting a bounded worker', function (int $executionLimit): void {
    travelTo(now('UTC')->next('Sunday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    $controller = Mockery::mock(WebcronController::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $controller->shouldReceive('executionLimit')->andReturn($executionLimit);
    app()->instance(WebcronController::class, $controller);
    Process::fake(function (PendingProcess $process) {
        Queue::assertPushed(ExpireEventGroupMembershipRequests::class);
        Queue::assertPushedOn('maintenance', DispatchStoryImageWarming::class);

        return Process::result(output: 'Jobs processed');
    });

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertOk()->assertSeeText('Scheduler:')->assertSeeText('Jobs processed');

    Process::assertRan(fn (PendingProcess $process): bool => $process->timeout === 90
        && $process->path === base_path()
        && $process->command === [PHP_BINDIR.'/php', 'artisan', 'webcron:work', '--no-interaction']);
    expect(Cache::lock('webcron-worker', 120)->get())->toBeTrue();
})->with(['hosting limit' => 300, 'minimum limit' => 120, 'unlimited' => 0]);

it('returns 503 for a reduced execution limit while still dispatching scheduled work', function (): void {
    travelTo(now('UTC')->next('Sunday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    Process::fake();
    $controller = Mockery::mock(WebcronController::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $controller->shouldReceive('executionLimit')->andReturn(30);
    app()->instance(WebcronController::class, $controller);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertServiceUnavailable()->assertSeeText('max_execution_time');

    Queue::assertPushed(ExpireEventGroupMembershipRequests::class);
    Queue::assertPushed(DispatchStoryImageWarming::class);
    Process::assertNothingRan();
});

it('rejects a synchronous queue before invoking the scheduler', function (): void {
    config(['queue.default' => 'sync']);
    Process::fake();
    Artisan::shouldReceive('call')->never();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertServiceUnavailable()->assertSeeText('QUEUE_CONNECTION=database');

    Process::assertNothingRan();
});

it('skips a concurrent worker but recovers when its crash lock expires', function (): void {
    travelTo(now('UTC')->next('Sunday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    Process::fake();
    Cache::lock('webcron-worker', 120)->get();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertOk()->assertSeeText('Worker already running');
    Queue::assertPushed(ExpireEventGroupMembershipRequests::class);
    Process::assertNothingRan();

    travel(121)->seconds();
    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertOk();
    Process::assertRan(fn (PendingProcess $process): bool => in_array('webcron:work', $process->command, true));
});

it('returns 500 and releases the lock when the worker fails', function (): void {
    Process::fake(['*' => Process::result(errorOutput: 'CLI runtime failed', exitCode: 1)]);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertInternalServerError()->assertSeeText('CLI runtime failed');

    Process::assertRan(fn (PendingProcess $process): bool => in_array('webcron:work', $process->command, true));
    expect(Cache::lock('webcron-worker', 120)->get())->toBeTrue();
});

it('continues queued work and returns 500 when scheduler orchestration fails', function (): void {
    Artisan::shouldReceive('call')->with('schedule:run')->once()->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('Scheduler failed');
    Process::fake();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertInternalServerError()->assertSeeText('Scheduler failed');

    Process::assertRan(fn (PendingProcess $process): bool => in_array('webcron:work', $process->command, true));
});

it('rejects an unsafe CLI queue reservation', function (): void {
    config(['queue.connections.database.retry_after' => 90]);

    artisan('webcron:work')->expectsOutputToContain('retry_after of at least 120')->assertFailed();
});

it('returns 500 and releases the lock when process execution throws', function (): void {
    Exceptions::fake();
    $failure = new RuntimeException('Worker process interrupted');
    Process::fake(fn () => throw $failure);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertInternalServerError()->assertSeeText('90-second deadline');

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $failure);
    expect(Cache::lock('webcron-worker', 120)->get())->toBeTrue();
});

it('returns 500 when a scheduled task fails despite a successful scheduler exit', function (): void {
    Exceptions::fake();
    Process::fake();
    Schedule::call(fn () => throw new RuntimeException('Scheduled dispatch failed'))->everyMinute();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertInternalServerError();

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Scheduled dispatch failed');
    Process::assertRan(fn (PendingProcess $process): bool => in_array('webcron:work', $process->command, true));
});

it('warms images nightly without dispatching cleanup on other weekdays', function (): void {
    travelTo(now('UTC')->next('Monday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    Process::fake();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertOk();

    Queue::assertNotPushed(ExpireEventGroupMembershipRequests::class);
    Queue::assertPushedOn('maintenance', DispatchStoryImageWarming::class);
    Process::assertRan(fn (PendingProcess $process): bool => in_array('webcron:work', $process->command, true));
});

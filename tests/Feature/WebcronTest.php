<?php

use App\Http\Controllers\WebcronController;
use App\Jobs\DispatchStoryImageWarming;
use App\Jobs\ExpireEventGroupMembershipRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Output\BufferedOutput;

use function Pest\Laravel\get;
use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;
use function Pest\Laravel\withServerVariables;

beforeEach(function (): void {
    config(['queue.default' => 'database', 'app.api_key' => 'webcron-test-key']);
});

it('dispatches scheduled work before running a bounded worker in the request', function (int $executionLimit): void {
    travelTo(now('UTC')->next('Sunday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    $controller = Mockery::mock(WebcronController::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $controller->shouldReceive('executionLimit')->andReturn($executionLimit);
    app()->instance(WebcronController::class, $controller);
    $kernel = Artisan::getFacadeRoot();
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))
        ->once()->ordered()->andReturnUsing(fn (string $command, array $options, BufferedOutput $output): int => $kernel->call($command, $options, $output));
    Artisan::shouldReceive('call')->with('queue:work', Mockery::on(function (array $options) use ($executionLimit): bool {
        Queue::assertPushed(ExpireEventGroupMembershipRequests::class);
        Queue::assertPushedOn('maintenance', DispatchStoryImageWarming::class);

        return $options['--stop-when-empty'] && $options['--tries'] === 3
            && $options['--queue'] === 'default,maintenance'
            && $options['--max-time'] > 0 && $options['--max-time'] <= min(60, $executionLimit ?: 60) - 35;
    }), Mockery::type(BufferedOutput::class))->once()->ordered()
        ->andReturnUsing(function (string $command, array $options, BufferedOutput $output): int {
            $output->writeln('Jobs processed');

            return 0;
        });

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertOk()->assertSeeText('Scheduler:')->assertSeeText('Jobs processed');

    expect(Cache::lock('webcron-worker', 90)->get())->toBeTrue();
})->with(['hosting limit' => 300, 'one minute' => 60, 'shorter limit' => 55, 'unlimited' => 0]);

it('skips queue work when the request has no time left for another job', function (int $executionLimit, int $elapsed): void {
    $controller = Mockery::mock(WebcronController::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $controller->shouldReceive('executionLimit')->andReturn($executionLimit);
    app()->instance(WebcronController::class, $controller);
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('queue:work', Mockery::any(), Mockery::any())->never();

    withServerVariables(['REQUEST_TIME_FLOAT' => microtime(true) - $elapsed]);
    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertServiceUnavailable()->assertSeeText('insufficient execution time');
})->with(['reduced PHP limit' => [30, 0], 'slow scheduler or bootstrap' => [300, 30]]);

it('subtracts elapsed request time from the worker budget', function (): void {
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('queue:work', Mockery::on(fn (array $options): bool => $options['--max-time'] > 0 && $options['--max-time'] <= 15), Mockery::type(BufferedOutput::class))->once()->andReturn(0);

    withServerVariables(['REQUEST_TIME_FLOAT' => microtime(true) - 10]);
    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertOk();
});

it('rejects a synchronous queue before invoking the scheduler', function (): void {
    config(['queue.default' => 'sync']);
    Artisan::shouldReceive('call')->never();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertServiceUnavailable()->assertSeeText('QUEUE_CONNECTION=database');
});

it('skips a concurrent worker but recovers when its crash lock expires', function (): void {
    travelTo(now('UTC')->next('Sunday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    Cache::lock('webcron-worker', 90)->get();
    $kernel = Artisan::getFacadeRoot();
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))
        ->twice()->andReturnUsing(fn (string $command, array $options, BufferedOutput $output): int => $kernel->call($command, $options, $output));
    Artisan::shouldReceive('call')->with('queue:work', Mockery::any(), Mockery::type(BufferedOutput::class))->once()->andReturn(0);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])
        ->assertOk()->assertSeeText('Worker already running');
    Queue::assertPushed(ExpireEventGroupMembershipRequests::class);

    travel(91)->seconds();
    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertOk();
});

it('returns 500 and releases the lock when the worker fails', function (): void {
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('queue:work', Mockery::any(), Mockery::type(BufferedOutput::class))->once()->andReturn(1);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertInternalServerError();

    expect(Cache::lock('webcron-worker', 90)->get())->toBeTrue();
});

it('continues queued work and returns 500 when scheduler orchestration fails', function (): void {
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))->once()->ordered()->andReturn(1);
    Artisan::shouldReceive('call')->with('queue:work', Mockery::any(), Mockery::type(BufferedOutput::class))->once()->ordered()->andReturn(0);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertInternalServerError();
});

it('reports worker exceptions and releases the lock', function (): void {
    Exceptions::fake();
    $failure = new RuntimeException('Worker interrupted');
    Artisan::shouldReceive('call')->with('schedule:run', [], Mockery::type(BufferedOutput::class))->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('queue:work', Mockery::any(), Mockery::type(BufferedOutput::class))->once()->andThrow($failure);

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertInternalServerError();

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $failure);
    expect(Cache::lock('webcron-worker', 90)->get())->toBeTrue();
});

it('returns 500 when a scheduled task fails despite a successful scheduler exit', function (): void {
    Exceptions::fake();
    Schedule::call(fn () => throw new RuntimeException('Scheduled dispatch failed'))->everyMinute();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertInternalServerError();

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Scheduled dispatch failed');
});

it('warms images nightly without dispatching cleanup on other weekdays', function (): void {
    travelTo(now('UTC')->next('Monday')->setTime(1, 0));
    Queue::fake([ExpireEventGroupMembershipRequests::class, DispatchStoryImageWarming::class]);
    Cache::lock('webcron-worker', 90)->get();

    get(route('queue-worker'), ['X-API-Key' => 'webcron-test-key'])->assertOk();

    Queue::assertNotPushed(ExpireEventGroupMembershipRequests::class);
    Queue::assertPushedOn('maintenance', DispatchStoryImageWarming::class);
});

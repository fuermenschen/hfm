<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class WebcronController extends Controller
{
    public function index(Request $request): Response
    {
        $connection = config('queue.default');
        if (config("queue.connections.{$connection}.driver") !== 'database') {
            return $this->respond("Webcron requires QUEUE_CONNECTION=database; scheduler and worker skipped.\n", 503);
        }

        $startedAt = (float) $request->server('REQUEST_TIME_FLOAT', microtime(true));
        $output = new BufferedOutput;
        $scheduledTaskFailed = false;
        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event) use (&$scheduledTaskFailed): void {
            $scheduledTaskFailed = true;
        });

        $output->writeln('Scheduler:');
        try {
            $schedulerExitCode = Artisan::call('schedule:run', [], $output);
            $status = $schedulerExitCode !== 0 || $scheduledTaskFailed ? 500 : 200;
        } catch (Throwable $throwable) {
            report($throwable);
            $output->writeln('Scheduler failed; see application logs.');
            $status = 500;
        }

        /** Allow an expected 30 seconds for the last job and 5 seconds for the response. */
        $budget = (int) floor(min(60, $this->executionLimit() ?: 60) - (microtime(true) - $startedAt) - 35);
        if ($budget < 1) {
            $output->writeln('Worker skipped: insufficient execution time remaining.');

            return $this->respond($output->fetch(), 503);
        }

        $lock = Cache::lock('webcron-worker', 360);
        if (! $lock->get()) {
            $output->writeln('Worker already running.');

            return $this->respond($output->fetch(), $status);
        }

        try {
            $output->writeln('Worker:');
            // queue:work runs in this request, so its limit must include existing request memory.
            $workerMemoryLimit = (int) ceil(memory_get_usage(true) / 1024 / 1024) + 128;
            $workerExitCode = Artisan::call('queue:work', [
                '--memory' => $workerMemoryLimit,
                '--stop-when-empty' => true,
                '--tries' => 3,
                '--max-time' => $budget,
                '--queue' => implode(',', array_unique([config("queue.connections.{$connection}.queue"), 'maintenance'])),
            ], $output);
            if ($workerExitCode !== 0) {
                $status = 500;
            }
        } finally {
            $lock->release();
        }

        return $this->respond($output->fetch(), $status);
    }

    protected function executionLimit(): int
    {
        return (int) ini_get('max_execution_time');
    }

    protected function respond(string $output, int $status): Response
    {
        return response($output, $status, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}

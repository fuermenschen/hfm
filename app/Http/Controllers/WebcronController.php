<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class WebcronController extends Controller
{
    public function index(): Response
    {
        $connection = config('queue.default');
        if (config("queue.connections.{$connection}.driver") !== 'database') {
            Log::error('Webcron requires an asynchronous database queue; scheduler and worker skipped.');

            return $this->respond("Webcron requires QUEUE_CONNECTION=database; scheduler and worker skipped.\n", 503);
        }

        $output = '';
        $status = 200;
        $scheduledTaskFailed = false;
        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event) use (&$scheduledTaskFailed): void {
            $scheduledTaskFailed = true;
        });

        try {
            $schedulerExitCode = Artisan::call('schedule:run');
            $output = "Scheduler:\n".Artisan::output();
            if ($schedulerExitCode !== 0 || $scheduledTaskFailed) {
                $status = 500;
            }
        } catch (Throwable $throwable) {
            report($throwable);
            $output = "Scheduler failed; see application logs.\n";
            $status = 500;
        }

        $executionLimit = $this->executionLimit();
        if ($executionLimit > 0 && $executionLimit < 120) {
            Log::error('Webcron worker skipped: max_execution_time must be 0 or at least 120 seconds.', ['max_execution_time' => $executionLimit]);

            return $this->respond($output."\nWorker skipped: max_execution_time must be 0 or at least 120 seconds.\n", 503);
        }

        $lock = Cache::lock('webcron-worker', 120);
        if (! $lock->get()) {
            return $this->respond($output."\nWorker already running.\n", $status);
        }

        try {
            $result = Process::path(base_path())->timeout(90)->run([
                PHP_BINDIR.'/php', 'artisan', 'webcron:work', '--no-interaction',
            ]);
            $output .= "\nWorker:\n".$result->output().$result->errorOutput();
            if (! $result->successful()) {
                Log::error('Webcron worker exited unsuccessfully.', ['exit_code' => $result->exitCode()]);
                $status = 500;
            }
        } catch (Throwable $throwable) {
            report($throwable);
            $output .= "\nWorker failed or exceeded its 90-second deadline; see application logs.\n";
            $status = 500;
        } finally {
            $lock->release();
        }

        return $this->respond($output, $status);
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

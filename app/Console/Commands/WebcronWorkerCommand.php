<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Validate CLI runtime and drain bounded webcron queue work')]
#[Signature('webcron:work')]
class WebcronWorkerCommand extends Command
{
    public function handle(): int
    {
        $connection = config('queue.default');
        $executionLimit = (int) ini_get('max_execution_time');

        if (! extension_loaded('pcntl') || ($executionLimit > 0 && $executionLimit < 120)) {
            $this->error('Webcron requires CLI PCNTL and max_execution_time of 0 or at least 120 seconds.');

            return self::FAILURE;
        }

        if (config("queue.connections.{$connection}.driver") !== 'database'
            || config("queue.connections.{$connection}.retry_after") < 120) {
            $this->error('Webcron requires a database queue with retry_after of at least 120 seconds.');

            return self::FAILURE;
        }

        return $this->call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 3,
            '--max-time' => 20,
            '--timeout' => 60,
            '--queue' => implode(',', array_unique([config("queue.connections.{$connection}.queue"), 'maintenance'])),
        ]);
    }
}

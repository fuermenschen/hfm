<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if (! $app->environment('local') || ! isset($kernel->all()['boost:update'])) {
    exit(0);
}

$status = $kernel->call('boost:update', [
    '--discover' => true,
    '--ansi' => true,
    '--no-interaction' => true,
], new ConsoleOutput);

if ($status !== 0) {
    exit($status);
}

if (file_exists($app->basePath('AGENTS.md'))) {
    $githubDirectory = $app->basePath('.github');

    if (! is_dir($githubDirectory) && ! mkdir($githubDirectory, 0777, true)) {
        throw new RuntimeException('Unable to create .github directory.');
    }

    if (! copy($app->basePath('AGENTS.md'), $githubDirectory.'/copilot-instructions.md')) {
        throw new RuntimeException('Unable to copy Copilot instructions.');
    }
}

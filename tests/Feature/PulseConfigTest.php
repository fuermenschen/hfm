<?php

use Laravel\Pulse\Recorders\CacheInteractions;
use Laravel\Pulse\Recorders\SlowRequests;
use Laravel\Pulse\Recorders\UserRequests;

it('groups high-cardinality cache keys on the pulse dashboard', function (string $key, string $group): void {
    $resolved = $key;

    foreach (config('pulse.recorders.'.CacheInteractions::class.'.groups') as $pattern => $replacement) {
        $resolved = preg_replace($pattern, $replacement, $resolved, count: $count);

        if ($count > 0) {
            break;
        }
    }

    expect($resolved)->toBe($group);
})->with(fn () => [
    'story image base' => ['story-image-base:'.str_repeat('a', 64), 'story-image-base:*'],
    'story image' => ['story-image:'.str_repeat('a', 64), 'story-image:*'],
    'log viewer' => ['lv:3:default:laravel-2026-08-23', 'lv:*'],
    'login link' => ['login-link:'.hash('sha256', 'foo@bar.ch'), 'login-link:*'],
    'login link ip' => ['login-link-ip:'.hash('sha256', '1.2.3.4'), 'login-link:*'],
    'athlete login link' => ['athlete-registration-login-link-ip:'.hash('sha256', '1.2.3.4'), 'athlete-registration-login-link:*'],
    'donor login link' => ['donor-registration-login-link:'.sha1('foo@bar.ch'), 'donor-registration-login-link:*'],
    'results component' => ['components.results.data.'.str_repeat('b', 64), 'components.results.data.*'],
    'vendor default' => ['job-exceptions:xyz', 'job-exceptions:*'],
    'ungrouped key stays as-is' => ['some-other-key', 'some-other-key'],
]);

it('ignores pulse dashboard requests at its actual path', function (string $path): void {
    foreach ([SlowRequests::class, UserRequests::class] as $recorder) {
        $ignored = collect(config('pulse.recorders.'.$recorder.'.ignore'))
            ->contains(fn (string $pattern): bool => preg_match($pattern, $path) === 1);

        expect($ignored)->toBeTrue();
    }
})->with(fn () => [
    'dashboard path' => ['/admin/pulse'],
    'trailing slash' => ['/admin/pulse/'],
    'subpath' => ['/admin/pulse/some/card'],
]);

<?php

use App\Models\ExternalUser;
use App\Models\User;
use App\Support\Pulse\ResolvesUsers;

it('prefixes user keys per guard to avoid id collisions', function (): void {
    $resolver = app(ResolvesUsers::class);

    expect($resolver->key(User::factory()->make(['id' => 5])))->toBe('admin:5')
        ->and($resolver->key(ExternalUser::factory()->make(['id' => 5])))->toBe('external:5');
});

it('loads and finds both admin and external users', function (): void {
    $admin = User::factory()->create(['name' => 'Jane Admin', 'email' => 'jane@example.com']);
    $external = ExternalUser::factory()->create([
        'first_name' => 'Max',
        'last_name' => 'Muster',
        'email' => 'max@example.com',
    ]);

    // Legacy bare numeric keys are included to prove load() skips them.
    $resolver = app(ResolvesUsers::class);
    $resolver->load(collect(['admin:'.$admin->id, 'external:'.$external->id, 42, null]));

    $adminResult = $resolver->find('admin:'.$admin->id);
    $externalResult = $resolver->find('external:'.$external->id);

    expect($adminResult->name)->toBe('Jane Admin')
        ->and($adminResult->extra)->toBe('jane@example.com')
        ->and($adminResult->avatar)->toContain('gravatar.com/avatar/')
        ->and($externalResult->name)->toBe('Max Muster')
        ->and($externalResult->extra)->toBe('max@example.com')
        // External emails must not be hashed into Gravatar URLs.
        ->and($externalResult->avatar)->toBe('https://gravatar.com/avatar?d=mp');
});

it('falls back to an id label for unknown or legacy bare keys', function (): void {
    $resolver = app(ResolvesUsers::class);
    $resolver->load(collect([42, null]));

    $legacy = $resolver->find('42');
    $missing = $resolver->find('admin:999');

    expect($legacy->name)->toBe('ID: 42')
        ->and($missing->name)->toBe('ID: admin:999');
});

it('is bound as the pulse user resolver', function (): void {
    expect(app(Laravel\Pulse\Contracts\ResolvesUsers::class))->toBeInstanceOf(ResolvesUsers::class);
});

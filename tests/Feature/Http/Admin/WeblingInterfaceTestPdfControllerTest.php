<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('streams the test pdf for authenticated users with a valid signed URL', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('webling/test-1234.pdf', '%PDF-1.4 fake');

    $user = User::factory()->create();
    actingAs($user);

    $url = URL::temporarySignedRoute(
        'admin.tools.webling-interface-test.pdf',
        now()->addMinutes(5),
        ['path' => encrypt('webling/test-1234.pdf')]
    );

    get($url)
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns forbidden when the encrypted path does not match expected pattern', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $url = URL::temporarySignedRoute(
        'admin.tools.webling-interface-test.pdf',
        now()->addMinutes(5),
        ['path' => encrypt('malicious/path.pdf')]
    );

    get($url)->assertForbidden();
});

it('returns forbidden for traversal-like encrypted paths', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $url = URL::temporarySignedRoute(
        'admin.tools.webling-interface-test.pdf',
        now()->addMinutes(5),
        ['path' => encrypt('webling/test-../../secret.pdf')]
    );

    get($url)->assertForbidden();
});

it('returns not found when a validly named file does not exist', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    actingAs($user);

    $url = URL::temporarySignedRoute(
        'admin.tools.webling-interface-test.pdf',
        now()->addMinutes(5),
        ['path' => encrypt('webling/test-9999.pdf')]
    );

    get($url)->assertNotFound();
});

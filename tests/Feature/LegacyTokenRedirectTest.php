<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('returns not found for legacy athlete token route', function (): void {
    get('/sportlerinnen/legacy-token')->assertNotFound();
    assertGuest('external');
});

it('returns not found for legacy donor token routes', function (): void {
    get('/spenderinnen/legacy-token')->assertNotFound();
    get('/spenderinnen/legacy-token/123')->assertNotFound();
    assertGuest('external');
});

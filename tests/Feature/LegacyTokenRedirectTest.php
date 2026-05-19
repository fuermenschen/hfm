<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns not found for legacy athlete token route', function (): void {
    $this->get('/sportlerinnen/legacy-token')->assertNotFound();
    $this->assertGuest('external');
});

it('returns not found for legacy donor token routes', function (): void {
    $this->get('/spenderinnen/legacy-token')->assertNotFound();
    $this->get('/spenderinnen/legacy-token/123')->assertNotFound();
    $this->assertGuest('external');
});

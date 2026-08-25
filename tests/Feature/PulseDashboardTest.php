<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the pulse dashboard with its stylesheet', function (): void {
    actingAs(User::factory()->create());

    $response = get('/admin/pulse');

    $response->assertSuccessful();
    // The Pulse card stylesheet pins card heights; without it the grid reflows
    // on every lazy card mount and poll refresh.
    $response->assertSee('basis-56', false);
});

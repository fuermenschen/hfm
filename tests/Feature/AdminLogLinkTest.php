<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows a link to the log viewer in the admin navigation that opens in a new tab', function () {
    $user = User::factory()->create();
    actingAs($user);

    $response = get(route('admin.dashboard'));

    $response->assertSee('/admin/logs');
    $response->assertSee('target="_blank"', false);
});

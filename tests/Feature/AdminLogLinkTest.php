<?php

use App\Models\User;

it('shows a link to the log viewer in the admin navigation that opens in a new tab', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));

    $response->assertSee('/admin/logs');
    $response->assertSee('target="_blank"', false);
});

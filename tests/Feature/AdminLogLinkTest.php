<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows admin monitoring links that open in a new tab', function () {
    $user = User::factory()->create();
    actingAs($user);

    $response = get(route('admin.dashboard'));

    $response->assertSee('/admin/logs');
    $response->assertSee('target="_blank"', false);

    preg_match(
        '/<a\s+[^>]*href="'.preg_quote(route('pulse'), '/').'"[^>]*>/s',
        $response->getContent(),
        $matches,
    );

    expect($matches[0] ?? null)->toContain('target="_blank"');
    expect($matches[0] ?? null)->toContain('rel="noopener noreferrer"');
});

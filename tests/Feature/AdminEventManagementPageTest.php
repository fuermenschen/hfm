<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the event management page to admins', function (): void {
    actingAs(User::factory()->create());

    get(route('admin.event-management'))
        ->assertOk()
        ->assertSee('Startnummern')
        ->assertSee('Runden zählen');
});

it('requires authentication', function (): void {
    get(route('admin.event-management'))
        ->assertRedirect(route('login'));
});

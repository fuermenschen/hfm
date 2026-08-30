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

it('uses the collapsible Flux sidebar shell', function (): void {
    actingAs(User::factory()->create(['name' => 'Admin Beispiel']));

    get(route('admin.event-management'))
        ->assertOk()
        ->assertSee('data-flux-sidebar')
        ->assertSee('collapsible="true"', false)
        ->assertSee('overflow-x-hidden')
        ->assertSee('transition-[width,padding,transform]!')
        ->assertSee('Höhenmeter für Menschen')
        ->assertSee('Admin Beispiel')
        ->assertSee('Ausloggen');
});

it('requires authentication', function (): void {
    get(route('admin.event-management'))
        ->assertRedirect(route('login'));
});

<?php

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the admin dashboard for authenticated users', function () {
    $user = User::factory()->create();

    actingAs($user);

    get('/admin')
        ->assertSuccessful()
        ->assertSee('Sportler:innen')
        ->assertSee('Spenden (tatsächlich)')
        ->assertSee('Letzte Aktivitäten');
});

it('renders partner cards even when partner totals are missing', function () {
    $user = User::factory()->create();
    Partner::factory()->create(['name' => 'Test Partner']);

    actingAs($user);

    get('/admin')
        ->assertSuccessful()
        ->assertSee('Test Partner');
});

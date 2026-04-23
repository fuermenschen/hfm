<?php

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the admin dashboard for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Sportler:innen')
        ->assertSee('Spenden (tatsächlich)')
        ->assertSee('Letzte Aktivitäten');
});

it('renders partner cards even when partner totals are missing', function () {
    $user = User::factory()->create();
    Partner::query()->create(['name' => 'Test Partner']);

    $this->actingAs($user)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Test Partner');
});

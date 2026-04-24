<?php

use App\Components\AdminFaqTable;
use App\Components\AdminPartnerTable;
use App\Components\AdminSponsorTable;
use App\Models\User;

it('requires authentication to view event content admin pages', function (): void {
    $paths = [
        '/admin/partner',
        '/admin/sponsoren',
        '/admin/faqs',
    ];

    foreach ($paths as $path) {
        $this->get($path)->assertRedirect();
    }
});

it('renders event content admin pages and livewire tables for authenticated users', function (string $path, string $componentClass): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get($path)
        ->assertSuccessful()
        ->assertSeeLivewire($componentClass);
})->with([
    ['/admin/partner', AdminPartnerTable::class],
    ['/admin/sponsoren', AdminSponsorTable::class],
    ['/admin/faqs', AdminFaqTable::class],
]);

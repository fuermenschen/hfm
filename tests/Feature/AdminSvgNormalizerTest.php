<?php

use App\Components\AdminNormalizeSvgFiles;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('public');
});

it('normalizes public SVG files recursively', function (): void {
    Storage::disk('public')->put('partners/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1v1H0z"/></svg>');
    Storage::disk('public')->put('nested/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1v1H0z"/></svg>');
    Storage::disk('public')->put('broken/logo.svg', '<svg>');
    Storage::disk('public')->put('documents/readme.txt', 'text');

    actingAs(User::factory()->create());

    Livewire::test(AdminNormalizeSvgFiles::class)
        ->call('normalize')
        ->assertSet('result.scanned', 3)
        ->assertSet('result.normalized', 1)
        ->assertSet('result.unchanged', 1)
        ->assertSet('result.failed.0.path', 'broken/logo.svg');

    expect(Storage::disk('public')->get('partners/logo.svg'))->toContain('stroke="none"');
});

it('rejects unauthenticated normalization', function (): void {
    Livewire::test(AdminNormalizeSvgFiles::class)
        ->call('normalize')
        ->assertForbidden();
});

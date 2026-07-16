<?php

use App\Models\Partner;
use App\Models\Sponsor;
use App\Support\AdminFiles\AdminFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

it('stores arbitrary files in nested directories', function (): void {
    $path = app(AdminFileStorage::class)->store(
        UploadedFile::fake()->create('Annual Report.PDF', 1, 'application/pdf'),
        'documents/reports',
    );

    expect($path)->toBe('documents/reports/annual-report.pdf');

    Storage::disk('public')->assertExists($path);
});

it('suffixes duplicate filenames', function (): void {
    $storage = app(AdminFileStorage::class);

    $firstPath = $storage->store(UploadedFile::fake()->create('logo.svg'), 'partners');
    $secondPath = $storage->store(UploadedFile::fake()->create('logo.svg'), 'partners');

    expect($firstPath)->toBe('partners/logo.svg')
        ->and($secondPath)->toBe('partners/logo-2.svg');

    Storage::disk('public')->assertExists([$firstPath, $secondPath]);
});

it('lists files with optional extension filters', function (): void {
    Storage::disk('public')->put('partners/logo.svg', 'svg');
    Storage::disk('public')->put('partners/readme.txt', 'txt');
    Storage::disk('public')->put('partners/.gitignore', 'ignored');
    Storage::disk('public')->put('partners/nested/photo.webp', 'webp');

    $storage = app(AdminFileStorage::class);

    expect(collect($storage->files('partners'))->pluck('path')->all())->toBe([
        'partners/logo.svg',
        'partners/readme.txt',
    ])
        ->and(collect($storage->files('partners', recursive: true, extensions: ['svg', '.webp']))->pluck('path')->all())->toBe([
            'partners/logo.svg',
            'partners/nested/photo.webp',
        ]);
});

it('creates directories', function (): void {
    $path = app(AdminFileStorage::class)->createDirectory('documents', 'reports');

    expect($path)->toBe('documents/reports')
        ->and(Storage::disk('public')->directories('documents'))->toContain('documents/reports');
});

it('rejects traversal paths', function (): void {
    $storage = app(AdminFileStorage::class);

    expect(fn () => $storage->store(UploadedFile::fake()->create('file.txt'), '../private'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $storage->createDirectory('documents', '../private'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $storage->delete('partners/../secret.txt'))
        ->toThrow(InvalidArgumentException::class);
});

it('deletes unreferenced files', function (): void {
    Carbon::setTestNow('2026-07-15 12:34:56');

    Storage::disk('public')->put('documents/free.pdf', 'pdf');

    app(AdminFileStorage::class)->delete('documents/free.pdf');

    Storage::disk('public')->assertMissing('documents/free.pdf');
    Storage::disk('local')->assertExists('trash/admin-files/documents/free.deleted-20260715-123456.pdf');

    Carbon::setTestNow();
});

it('blocks deleting referenced partner and sponsor files', function (): void {
    $partner = Partner::query()->create([
        'name' => 'Referenced Partner',
        'logo_light_filename' => 'logo-light.svg',
        'logo_dark_filename' => 'logo-dark.svg',
    ]);

    $sponsor = Sponsor::query()->create([
        'name' => 'Referenced Sponsor',
        'description' => 'Main sponsor',
        'logo_filename' => 'logo.svg',
    ]);

    Storage::disk('public')->put('partners/logo-light.svg', 'svg');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    $storage = app(AdminFileStorage::class);

    expect($storage->references('partners/logo-light.svg'))->toContain([
        'label' => 'Partner:in Logo hell #'.$partner->id,
        'model' => Partner::class,
        'id' => $partner->id,
        'field' => 'logo_light_filename',
    ])
        ->and($storage->references('sponsors/logo.svg'))->toContain([
            'label' => 'Sponsor:in Logo #'.$sponsor->id,
            'model' => Sponsor::class,
            'id' => $sponsor->id,
            'field' => 'logo_filename',
        ])
        ->and(fn () => $storage->delete('partners/logo-light.svg'))->toThrow(RuntimeException::class)
        ->and(fn () => $storage->delete('sponsors/logo.svg'))->toThrow(RuntimeException::class);

    Storage::disk('public')->assertExists(['partners/logo-light.svg', 'sponsors/logo.svg']);
});

it('does not treat legacy root filenames as nested file references', function (): void {
    Partner::query()->create([
        'name' => 'Root Logo Partner',
        'logo_light_filename' => 'logo.svg',
    ]);

    Storage::disk('public')->put('partners/nested/logo.svg', 'svg');

    app(AdminFileStorage::class)->delete('partners/nested/logo.svg');

    Storage::disk('public')->assertMissing('partners/nested/logo.svg');
});

it('blocks deleting nested files referenced by legacy relative paths', function (): void {
    $partner = Partner::query()->create([
        'name' => 'Nested Logo Partner',
        'logo_light_filename' => 'nested/logo.svg',
    ]);

    $sponsor = Sponsor::query()->create([
        'name' => 'Nested Logo Sponsor',
        'description' => 'Nested sponsor',
        'logo_filename' => 'nested/logo.svg',
    ]);

    Storage::disk('public')->put('partners/nested/logo.svg', 'svg');
    Storage::disk('public')->put('sponsors/nested/logo.svg', 'svg');

    $storage = app(AdminFileStorage::class);

    expect($storage->references('partners/nested/logo.svg'))->toContain([
        'label' => 'Partner:in Logo hell #'.$partner->id,
        'model' => Partner::class,
        'id' => $partner->id,
        'field' => 'logo_light_filename',
    ])
        ->and($storage->references('sponsors/nested/logo.svg'))->toContain([
            'label' => 'Sponsor:in Logo #'.$sponsor->id,
            'model' => Sponsor::class,
            'id' => $sponsor->id,
            'field' => 'logo_filename',
        ])
        ->and(fn () => $storage->delete('partners/nested/logo.svg'))->toThrow(RuntimeException::class)
        ->and(fn () => $storage->delete('sponsors/nested/logo.svg'))->toThrow(RuntimeException::class);
});

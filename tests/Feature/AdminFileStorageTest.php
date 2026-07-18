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

it('rejects empty directory names in German', function (): void {
    expect(fn () => app(AdminFileStorage::class)->createDirectory('documents', ''))
        ->toThrow(InvalidArgumentException::class, 'Ordnername darf nicht leer sein.');
});

it('deletes empty directories', function (): void {
    Storage::disk('public')->makeDirectory('documents/empty');

    app(AdminFileStorage::class)->deleteEmptyDirectory('documents/empty');

    expect(Storage::disk('public')->directoryExists('documents/empty'))->toBeFalse();
});

it('does not delete non-empty directories', function (): void {
    Storage::disk('public')->put('documents/hidden/.gitkeep', '');
    Storage::disk('public')->makeDirectory('documents/nested/empty');

    $storage = app(AdminFileStorage::class);

    expect(fn () => $storage->deleteEmptyDirectory('documents/hidden'))
        ->toThrow(InvalidArgumentException::class, 'Nur leere Ordner können geändert werden.')
        ->and(fn () => $storage->deleteEmptyDirectory('documents/nested'))
        ->toThrow(InvalidArgumentException::class, 'Nur leere Ordner können geändert werden.');
});

it('renames empty directories', function (): void {
    Storage::disk('public')->makeDirectory('documents/old name');

    $path = app(AdminFileStorage::class)->renameEmptyDirectory('documents/old name', 'new name');

    expect($path)->toBe('documents/new name')
        ->and(Storage::disk('public')->directoryExists('documents/old name'))->toBeFalse()
        ->and(Storage::disk('public')->directoryExists('documents/new name'))->toBeTrue();
});

it('rejects directory rename collisions', function (): void {
    Storage::disk('public')->makeDirectory('documents/source');
    Storage::disk('public')->makeDirectory('documents/target');

    expect(fn () => app(AdminFileStorage::class)->renameEmptyDirectory('documents/source', 'target'))
        ->toThrow(InvalidArgumentException::class, 'Eine Datei oder ein Ordner mit diesem Namen existiert bereits.');
});

it('renames files while preserving their extension', function (): void {
    Storage::disk('public')->put('documents/old-report.pdf', 'pdf');

    $path = app(AdminFileStorage::class)->renameFile('documents/old-report.pdf', 'Final Report.pdf');

    expect($path)->toBe('documents/final-report.pdf');
    Storage::disk('public')->assertMissing('documents/old-report.pdf');
    Storage::disk('public')->assertExists('documents/final-report.pdf');
});

it('rejects file extension changes and collisions', function (): void {
    Storage::disk('public')->put('documents/source.pdf', 'pdf');
    Storage::disk('public')->put('documents/target.pdf', 'pdf');

    $storage = app(AdminFileStorage::class);

    expect(fn () => $storage->renameFile('documents/source.pdf', 'source.txt'))
        ->toThrow(InvalidArgumentException::class, 'Die Dateiendung darf nicht geändert werden.')
        ->and(fn () => $storage->renameFile('documents/source.pdf', 'target.pdf'))
        ->toThrow(InvalidArgumentException::class, 'Eine Datei oder ein Ordner mit diesem Namen existiert bereits.');
});

it('throws when directory creation fails', function (): void {
    Storage::shouldReceive('disk')
        ->with('public')
        ->andReturn(new class
        {
            public function makeDirectory(string $path): bool
            {
                return false;
            }
        });

    expect(fn () => app(AdminFileStorage::class)->createDirectory('documents', 'reports'))
        ->toThrow(RuntimeException::class, 'Ordner konnte nicht erstellt werden.');
});

it('rejects traversal paths', function (): void {
    $storage = app(AdminFileStorage::class);

    expect(fn () => $storage->store(UploadedFile::fake()->create('file.txt'), '../private'))
        ->toThrow(InvalidArgumentException::class, 'Ungültiger Dateipfad.')
        ->and(fn () => $storage->createDirectory('documents', '../private'))
        ->toThrow(InvalidArgumentException::class, 'Ungültiger Dateipfad.')
        ->and(fn () => $storage->delete('partners/../secret.txt'))
        ->toThrow(InvalidArgumentException::class, 'Ungültiger Dateipfad.')
        ->and(fn () => $storage->normalizePath(''))
        ->toThrow(InvalidArgumentException::class, 'Dateipfad darf nicht leer sein.');
});

it('deletes unreferenced files', function (): void {
    Carbon::setTestNow('2026-07-15 12:34:56');

    Storage::disk('public')->put('documents/free.pdf', 'pdf');

    app(AdminFileStorage::class)->delete('documents/free.pdf');

    Storage::disk('public')->assertMissing('documents/free.pdf');
    $trashedFiles = Storage::disk('local')->files('trash/admin-files/documents');

    expect($trashedFiles)->toHaveCount(1)
        ->and($trashedFiles[0])->toStartWith('trash/admin-files/documents/free.deleted-20260715-123456-')
        ->toEndWith('.pdf');

    Carbon::setTestNow();
});

it('throws when public file deletion fails after trashing', function (): void {
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, 'pdf');
    rewind($stream);

    $publicDisk = new class($stream)
    {
        public function __construct(private mixed $stream) {}

        public function readStream(string $path): mixed
        {
            return $this->stream;
        }

        public function delete(string $path): bool
        {
            return false;
        }
    };

    $localDisk = new class
    {
        public function put(string $path, mixed $contents): bool
        {
            return true;
        }
    };

    Storage::shouldReceive('disk')->with('public')->andReturn($publicDisk);
    Storage::shouldReceive('disk')->with('local')->andReturn($localDisk);

    expect(fn () => app(AdminFileStorage::class)->delete('documents/free.pdf'))
        ->toThrow(RuntimeException::class, 'Datei konnte nicht gelöscht werden.');
});

it('throws when public file cannot be opened for trashing', function (): void {
    $publicDisk = new class
    {
        public function readStream(string $path): false
        {
            return false;
        }
    };

    Storage::shouldReceive('disk')->with('public')->andReturn($publicDisk);

    expect(fn () => app(AdminFileStorage::class)->delete('documents/free.pdf'))
        ->toThrow(RuntimeException::class, 'Datei konnte nicht in den Papierkorb verschoben werden.');
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
        ->and(fn () => $storage->delete('sponsors/logo.svg'))->toThrow(RuntimeException::class)
        ->and(fn () => $storage->renameFile('partners/logo-light.svg', 'renamed.svg'))->toThrow(RuntimeException::class)
        ->and(fn () => $storage->renameFile('sponsors/logo.svg', 'renamed.svg'))->toThrow(RuntimeException::class);

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

<?php

use App\Components\AdminFiles;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

it('renders the admin files page', function (): void {
    actingAs(User::factory()->create());

    get('/admin/dateien')
        ->assertSuccessful()
        ->assertSee('Dateien');
});

it('uploads files to selected directories', function (): void {
    Livewire::test(AdminFiles::class)
        ->set('directory', 'documents/reports')
        ->set('file', UploadedFile::fake()->create('Annual Report.pdf', 1, 'application/pdf'))
        ->assertSet('file', null)
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists('documents/reports/annual-report.pdf');
});

it('lists folders and opens directories', function (): void {
    Storage::disk('public')->put('documents/report.pdf', 'pdf');
    Storage::disk('public')->put('documents/.gitignore', 'ignored');

    Livewire::test(AdminFiles::class)
        ->assertSee('documents')
        ->call('openDirectory', 'documents')
        ->assertSet('directory', 'documents')
        ->assertSee('report.pdf')
        ->assertDontSee('.gitignore');
});

it('shows breadcrumbs and opens parent directories', function (): void {
    Storage::disk('public')->put('documents/reports/report.pdf', 'pdf');

    Livewire::test(AdminFiles::class)
        ->call('openDirectory', 'documents/reports')
        ->assertSee('Dateien')
        ->assertSee('documents')
        ->assertSee('reports')
        ->assertSee('..')
        ->call('openParentDirectory')
        ->assertSet('directory', 'documents');
});

it('creates folders in the current directory', function (): void {
    Livewire::test(AdminFiles::class)
        ->set('directory', 'documents')
        ->set('newFolderName', 'reports')
        ->call('createFolder')
        ->assertSet('directory', 'documents')
        ->assertSet('newFolderName', null)
        ->assertHasNoErrors();

    expect(Storage::disk('public')->directories('documents'))->toContain('documents/reports');
});

it('deletes unreferenced files', function (): void {
    Storage::disk('public')->put('documents/free.pdf', 'pdf');

    Livewire::test(AdminFiles::class)
        ->call('confirmDelete', 'documents/free.pdf')
        ->assertSet('pendingDeletePath', 'documents/free.pdf')
        ->call('deleteFile')
        ->assertSet('pendingDeletePath', null)
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing('documents/free.pdf');
});

it('blocks deleting referenced files', function (): void {
    $partner = Partner::query()->create([
        'name' => 'Referenced Partner',
        'logo_light_filename' => 'logo.svg',
    ]);

    Storage::disk('public')->put('partners/logo.svg', 'svg');

    Livewire::test(AdminFiles::class)
        ->call('confirmDelete', 'partners/logo.svg')
        ->assertSet('pendingDeleteReferences.0.label', 'Partner:in Logo hell #'.$partner->id)
        ->call('deleteFile')
        ->assertHasErrors('pendingDeletePath');

    Storage::disk('public')->assertExists('partners/logo.svg');
});

it('rejects invalid target directories', function (): void {
    Livewire::test(AdminFiles::class)
        ->set('directory', '../private')
        ->set('file', UploadedFile::fake()->create('file.txt'))
        ->call('storeFile')
        ->assertHasErrors('directory');
});

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

    actingAs(User::factory()->create());
});

it('renders the admin files page', function (): void {
    get('/admin/dateien')
        ->assertSuccessful()
        ->assertSee('Dateien')
        ->assertSee('Hochgeladene Dateien sind sofort über eine öffentliche URL abrufbar.');
});

it('rejects unauthenticated file mutations', function (): void {
    auth()->logout();

    Livewire::test(AdminFiles::class)
        ->set('newFolderName', 'reports')
        ->call('createFolder')
        ->assertForbidden();
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
        ->assertSee('rel="noopener noreferrer"', false)
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

it('handles invalid directory navigation', function (): void {
    Livewire::test(AdminFiles::class)
        ->call('openDirectory', '../private')
        ->assertSet('directory', '')
        ->assertHasErrors('directory')
        ->set('directory', '../private')
        ->call('openParentDirectory')
        ->assertSet('directory', '');
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

it('renames empty folders', function (): void {
    Storage::disk('public')->makeDirectory('documents/drafts');

    Livewire::test(AdminFiles::class)
        ->call('confirmRenameDirectory', 'documents/drafts')
        ->assertHasNoErrors()
        ->assertSet('pendingRenamePath', 'documents/drafts')
        ->set('newName', 'archive')
        ->call('renameEntry')
        ->assertSet('pendingRenamePath', null)
        ->assertHasNoErrors();

    expect(Storage::disk('public')->directoryExists('documents/drafts'))->toBeFalse()
        ->and(Storage::disk('public')->directoryExists('documents/archive'))->toBeTrue();
});

it('deletes empty folders', function (): void {
    Storage::disk('public')->makeDirectory('documents/empty');

    Livewire::test(AdminFiles::class)
        ->call('confirmDeleteDirectory', 'documents/empty')
        ->assertHasNoErrors()
        ->assertSet('pendingDeleteDirectory', 'documents/empty')
        ->call('deleteDirectory')
        ->assertSet('pendingDeleteDirectory', null)
        ->assertHasNoErrors();

    expect(Storage::disk('public')->directoryExists('documents/empty'))->toBeFalse();
});

it('does not delete non-empty folders', function (): void {
    Storage::disk('public')->put('documents/reports/file.txt', 'content');

    Livewire::test(AdminFiles::class)
        ->call('confirmDeleteDirectory', 'documents/reports')
        ->call('deleteDirectory')
        ->assertHasErrors('pendingDeleteDirectory');

    Storage::disk('public')->assertExists('documents/reports/file.txt');
});

it('renames unreferenced files', function (): void {
    Storage::disk('public')->put('documents/draft.pdf', 'pdf');

    Livewire::test(AdminFiles::class)
        ->call('confirmRenameFile', 'documents/draft.pdf')
        ->assertSet('pendingRenamePath', 'documents/draft.pdf')
        ->set('newName', 'Final.pdf')
        ->call('renameEntry')
        ->assertSet('pendingRenamePath', null)
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing('documents/draft.pdf');
    Storage::disk('public')->assertExists('documents/final.pdf');
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

it('rejects deleting files that are not listed as deletable', function (): void {
    Storage::disk('public')->put('documents/.secret', 'secret');

    Livewire::test(AdminFiles::class)
        ->call('confirmDelete', 'documents/.secret')
        ->assertHasErrors('pendingDeletePath')
        ->set('pendingDeletePath', 'documents/.secret')
        ->call('deleteFile')
        ->assertHasErrors('pendingDeletePath');

    Storage::disk('public')->assertExists('documents/.secret');
});

it('uses flux file upload without custom javascript upload handling', function (): void {
    Livewire::test(AdminFiles::class)
        ->assertSee('flux-file-upload', false)
        ->assertDontSee('$wire.upload', false);
});

it('limits uploads to 100 MB', function (): void {
    expect(config('livewire.temporary_file_upload.rules'))->toContain('max:102400')
        ->and((new AdminFiles)->rules()['file'])->toContain('max:102400');

    Livewire::test(AdminFiles::class)->assertSee('Maximal 100 MB');
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

it('blocks renaming referenced files', function (): void {
    $partner = Partner::query()->create([
        'name' => 'Referenced Partner',
        'logo_light_filename' => 'logo.svg',
    ]);

    Storage::disk('public')->put('partners/logo.svg', 'svg');

    Livewire::test(AdminFiles::class)
        ->call('confirmRenameFile', 'partners/logo.svg')
        ->assertSet('pendingRenameReferences.0.label', 'Partner:in Logo hell #'.$partner->id)
        ->set('newName', 'renamed.svg')
        ->call('renameEntry')
        ->assertHasErrors('newName');

    Storage::disk('public')->assertExists('partners/logo.svg');
    Storage::disk('public')->assertMissing('partners/renamed.svg');
});

it('rejects invalid target directories', function (): void {
    Livewire::test(AdminFiles::class)
        ->set('directory', '../private')
        ->set('file', UploadedFile::fake()->create('file.txt'))
        ->call('storeFile')
        ->assertHasErrors('directory');
});

it('rejects unsupported upload types', function (): void {
    Livewire::test(AdminFiles::class)
        ->set('file', UploadedFile::fake()->create('payload.html', 1, 'text/html'))
        ->assertHasErrors('file');
});

it('rejects supported mime types with unsupported extensions', function (): void {
    Livewire::test(AdminFiles::class)
        ->set('file', UploadedFile::fake()->create('payload.php', 1, 'text/plain'))
        ->assertHasErrors('file');
});

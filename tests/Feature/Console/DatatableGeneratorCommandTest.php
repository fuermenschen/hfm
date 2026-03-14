<?php

use Illuminate\Filesystem\Filesystem;

it('generates datatable class, view, and test files', function (): void {
    $files = app(Filesystem::class);

    $componentPath = app_path('Components/PartnersTable.php');
    $viewPath = resource_path('views/components/tables/partners-table.blade.php');
    $testPath = base_path('tests/Feature/Components/PartnersTableTest.php');

    foreach ([$componentPath, $viewPath, $testPath] as $path) {
        if ($files->exists($path)) {
            $files->delete($path);
        }
    }

    $this->artisan('make:datatable', [
        'context' => 'shared',
        'name' => 'PartnersTable',
        '--model' => 'Partner',
        '--view' => 'partners-table',
        '--columns' => 'id,name,email,created_at',
        '--searchable' => 'name,email',
        '--sortable' => 'id,name,created_at',
        '--visible' => 'id,name,email',
        '--export' => true,
        '--test' => true,
    ])->assertExitCode(0);

    expect($files->exists($componentPath))->toBeTrue();
    expect($files->exists($viewPath))->toBeTrue();
    expect($files->exists($testPath))->toBeTrue();

    expect($files->get($componentPath))->toContain('class PartnersTable extends AbstractDatatableComponent');
    expect($files->get($componentPath))->toContain("return 'components.tables.partners-table';");
    expect($files->get($componentPath))->toContain('use App\\Models\\Partner;');
    expect($files->get($componentPath))->toContain("public string \$sortField = 'id';");
    expect($files->get($componentPath))->toContain("'name' => ['label' => 'Name', 'sortable' => true");
    expect($files->get($componentPath))->toContain('public function exportAll(string $format): ?HttpResponse');
    expect($files->get($viewPath))->toContain('<x-datatable.partials.export-dropdown />');

    $files->delete($componentPath);
    $files->delete($viewPath);
    $files->delete($testPath);
});

it('fails when target files exist without force', function (): void {
    $files = app(Filesystem::class);

    $componentPath = app_path('Components/ExistingDemoTable.php');
    $viewPath = resource_path('views/components/tables/existing-demo-table.blade.php');

    $files->ensureDirectoryExists(dirname($componentPath));
    $files->ensureDirectoryExists(dirname($viewPath));
    $files->put($componentPath, '<?php');
    $files->put($viewPath, '<div></div>');

    $this->artisan('make:datatable', [
        'context' => 'shared',
        'name' => 'ExistingDemoTable',
    ])->assertExitCode(1);

    $files->delete($componentPath);
    $files->delete($viewPath);
});

it('can generate without exports and tests', function (): void {
    $files = app(Filesystem::class);

    $componentPath = app_path('Components/SlimTable.php');
    $viewPath = resource_path('views/components/tables/slim-table.blade.php');
    $testPath = base_path('tests/Feature/Components/SlimTableTest.php');

    foreach ([$componentPath, $viewPath, $testPath] as $path) {
        if ($files->exists($path)) {
            $files->delete($path);
        }
    }

    $this->artisan('make:datatable', [
        'context' => 'shared',
        'name' => 'SlimTable',
        '--model' => 'Partner',
        '--columns' => 'id,name',
        '--searchable' => 'name',
        '--sortable' => 'id,name',
        '--visible' => 'id,name',
    ])->assertExitCode(0);

    expect($files->exists($componentPath))->toBeTrue();
    expect($files->exists($viewPath))->toBeTrue();
    expect($files->exists($testPath))->toBeFalse();
    expect($files->get($componentPath))->not->toContain('exportAll');
    expect($files->get($viewPath))->not->toContain('<x-datatable.partials.export-dropdown />');

    $files->delete($componentPath);
    $files->delete($viewPath);
});

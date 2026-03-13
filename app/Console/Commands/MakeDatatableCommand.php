<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\warning;

class MakeDatatableCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'make:datatable
                            {context : Datatable context (admin, public, or shared)}
                            {name : Datatable component class (e.g. AdminPartnersTable)}
                            {--model= : Model class (e.g. Partner or App\\Models\\Partner)}
                            {--view= : Blade view slug (e.g. partners-table)}
                            {--columns= : Comma-separated table columns}
                            {--searchable= : Comma-separated searchable columns}
                            {--sortable= : Comma-separated sortable columns}
                            {--visible= : Comma-separated default visible columns}
                            {--export : Include export actions and methods}
                            {--test : Create a Pest feature test}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate an opinionated Livewire datatable with smart defaults and prompts';

    public function __construct(public Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->isPromptingEnabled()) {
            intro('Overpowered datatable generator');
        }

        $context = $this->normalizeContext((string) $this->argument('context'));
        $componentClass = $this->normalizeComponentClass((string) $this->argument('name'));

        $modelClass = $this->resolveModelClass($componentClass, $context);
        $viewSlug = $this->resolveViewSlug($componentClass, $context);
        $includeExport = $this->resolveExportFlag();
        $includeTest = $this->resolveTestFlag();

        $schema = $this->resolveModelSchema($modelClass);
        $allColumns = $this->resolveColumns($schema, $componentClass, $context);

        if ($allColumns === []) {
            $this->components->error('No columns could be resolved. Pass --columns explicitly.');

            return self::FAILURE;
        }

        $searchableColumns = $this->resolveSearchableColumns($allColumns, $schema['types']);
        $sortableColumns = $this->resolveSortableColumns($allColumns, $schema['types']);
        $visibleColumns = $this->resolveVisibleColumns($allColumns);

        $tableDataKey = $this->tableDataKey($componentClass, $context);
        $viewRelativeDirectory = $this->viewRelativeDirectory($context);
        $viewDotPath = str_replace('/', '.', $viewRelativeDirectory).'.'.$viewSlug;

        $componentPath = app_path('Components/'.$componentClass.'.php');
        $viewPath = resource_path('views/'.$viewRelativeDirectory.'/'.$viewSlug.'.blade.php');
        $testPath = base_path('tests/Feature/Components/'.$componentClass.'Test.php');

        $targetPaths = [$componentPath, $viewPath];

        if ($includeTest) {
            $targetPaths[] = $testPath;
        }

        $existingPaths = array_values(array_filter($targetPaths, fn (string $path): bool => $this->files->exists($path)));

        if ($existingPaths !== [] && ! (bool) $this->option('force')) {
            $this->components->error('Some target files already exist. Use --force to overwrite.');

            foreach ($existingPaths as $existingPath) {
                $this->line(' - '.$this->relativePath($existingPath));
            }

            return self::FAILURE;
        }

        $sortField = $sortableColumns[0] ?? 'id';
        $tableName = $schema['table'];

        $columnDefinitionsCode = $this->buildColumnDefinitionsCode($allColumns, $sortableColumns, $schema['types']);
        $sortColumnsCode = $this->buildSortColumnsCode($sortableColumns, $tableName);
        $defaultVisibleColumnsCode = $this->buildStringListCode($visibleColumns);
        $applySearchBody = $this->buildApplySearchBody($searchableColumns);
        $exportMethodsCode = $this->buildExportMethodsCode($includeExport, $allColumns);

        $replacements = [
            '{{ componentClass }}' => $componentClass,
            '{{ modelClass }}' => $modelClass,
            '{{ modelBase }}' => class_basename($modelClass),
            '{{ viewDotPath }}' => $viewDotPath,
            '{{ tableDataKey }}' => $tableDataKey,
            '{{ sortField }}' => $sortField,
            '{{ defaultSortColumn }}' => $tableName.'.'.$sortField,
            '{{ sortColumnsCode }}' => $sortColumnsCode,
            '{{ columnDefinitionsCode }}' => $columnDefinitionsCode,
            '{{ defaultVisibleColumnsCode }}' => $defaultVisibleColumnsCode,
            '{{ applySearchBody }}' => $applySearchBody,
            '{{ exportMethodsCode }}' => $exportMethodsCode,
            '{{ responseImport }}' => $includeExport ? 'use Symfony\\Component\\HttpFoundation\\Response as HttpResponse;' : '',
            '{{ exportToolbarBlock }}' => $includeExport ? '<x-datatable.partials.export-dropdown />' : '',
        ];

        $componentContents = $this->renderStub('stubs/datatable/component.stub', $replacements);
        $viewContents = $this->renderStub('stubs/datatable/view.stub', $replacements);
        $testContents = $this->renderStub('stubs/datatable/test.stub', $replacements);

        $this->writeFile($componentPath, $componentContents);
        $this->writeFile($viewPath, $viewContents);

        if ($includeTest) {
            $this->writeFile($testPath, $testContents);
        }

        $this->components->info('Datatable scaffold created.');
        note('Created files:');
        $this->line(' - '.$this->relativePath($componentPath));
        $this->line(' - '.$this->relativePath($viewPath));

        if ($includeTest) {
            $this->line(' - '.$this->relativePath($testPath));
        }

        if ($this->isPromptingEnabled()) {
            info('Next: register the component with @livewire(...) and adapt labels/formatters.');
            outro('Done.');
        }

        return self::SUCCESS;
    }

    protected function isPromptingEnabled(): bool
    {
        return $this->input->isInteractive() && ! app()->runningUnitTests();
    }

    protected function normalizeContext(string $context): string
    {
        $normalized = Str::of($context)->trim()->lower()->slug('-')->value();

        if ($normalized === '') {
            throw new \InvalidArgumentException('Context cannot be empty.');
        }

        return $normalized;
    }

    protected function normalizeComponentClass(string $name): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', Str::studly(trim($name))) ?? '';

        if ($normalized === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $normalized)) {
            throw new \InvalidArgumentException('Component class name must be alphanumeric and start with a letter.');
        }

        if (! Str::endsWith($normalized, 'Table')) {
            $normalized .= 'Table';
        }

        return $normalized;
    }

    protected function resolveModelClass(string $componentClass, string $context): string
    {
        $option = trim((string) ($this->option('model') ?? ''));

        if ($option !== '') {
            return Str::startsWith($option, 'App\\')
                ? $option
                : 'App\\Models\\'.Str::studly($option);
        }

        $default = 'App\\Models\\'.$this->defaultModelName($componentClass, $context);

        if (! $this->isPromptingEnabled()) {
            return $default;
        }

        $models = $this->availableModels();
        $defaultShort = class_basename($default);

        $selected = suggest(
            label: 'Which model should back this datatable?',
            options: array_map('class_basename', $models),
            default: $defaultShort,
            placeholder: 'E.g. Donator',
            required: true,
        );

        $selected = trim((string) $selected);

        return Str::startsWith($selected, 'App\\')
            ? $selected
            : 'App\\Models\\'.Str::studly($selected);
    }

    protected function defaultModelName(string $componentClass, string $context): string
    {
        $core = $this->componentCoreName($componentClass, $context);
        $core = Str::singular($core);

        return Str::studly($core !== '' ? $core : 'Item');
    }

    protected function componentCoreName(string $componentClass, string $context): string
    {
        $name = Str::beforeLast($componentClass, 'Table');

        if ($context === 'admin' && Str::startsWith($name, 'Admin')) {
            $name = Str::after($name, 'Admin');
        }

        return trim($name);
    }

    /**
     * @return array<int, string>
     */
    protected function availableModels(): array
    {
        $directory = app_path('Models');

        if (! $this->files->isDirectory($directory)) {
            return [];
        }

        $models = [];

        foreach ($this->files->allFiles($directory) as $file) {
            $relativePath = Str::after($file->getPathname(), $directory.DIRECTORY_SEPARATOR);
            $class = 'App\\Models\\'.str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath,
            );

            $models[] = $class;
        }

        sort($models);

        return $models;
    }

    protected function resolveViewSlug(string $componentClass, string $context): string
    {
        $option = trim((string) ($this->option('view') ?? ''));

        if ($option !== '') {
            return Str::of($option)
                ->replace('.blade.php', '')
                ->replace('/', '-')
                ->kebab()
                ->value();
        }

        $default = $this->defaultViewSlug($componentClass, $context);

        if (! $this->isPromptingEnabled()) {
            return $default;
        }

        return (string) suggest(
            label: 'Blade view slug?',
            options: [$default],
            default: $default,
            required: true,
        );
    }

    protected function defaultViewSlug(string $componentClass, string $context): string
    {
        $core = $this->componentCoreName($componentClass, $context);

        return Str::kebab($core !== '' ? $core : 'items').'-table';
    }

    protected function resolveExportFlag(): bool
    {
        if ((bool) $this->option('export')) {
            return true;
        }

        if (! $this->isPromptingEnabled()) {
            return false;
        }

        return confirm(
            label: 'Include export actions (CSV/XLSX)?',
            default: true,
        );
    }

    protected function resolveTestFlag(): bool
    {
        if ((bool) $this->option('test')) {
            return true;
        }

        if (! $this->isPromptingEnabled()) {
            return false;
        }

        return confirm(
            label: 'Generate a Pest feature test?',
            default: true,
        );
    }

    /**
     * @return array{table:string, columns:array<int, string>, types:array<string, string>}
     */
    protected function resolveModelSchema(string $modelClass): array
    {
        $fallback = [
            'table' => Str::snake(Str::pluralStudly(class_basename($modelClass))),
            'columns' => ['id'],
            'types' => ['id' => 'integer'],
        ];

        if (! class_exists($modelClass)) {
            if ($this->isPromptingEnabled()) {
                warning("Model class {$modelClass} does not exist yet. Falling back to minimal defaults.");
            }

            return $fallback;
        }

        $model = app($modelClass);

        if (! $model instanceof Model) {
            if ($this->isPromptingEnabled()) {
                warning("{$modelClass} is not an Eloquent model. Falling back to minimal defaults.");
            }

            return $fallback;
        }

        $table = $model->getTable();
        $connection = $model->getConnectionName();

        try {
            $schema = $connection !== null
                ? Schema::connection($connection)
                : Schema::connection(config('database.default'));

            $columns = $schema->getColumnListing($table);
            $types = [];

            foreach ($columns as $column) {
                $types[$column] = $schema->getColumnType($table, $column);
            }

            return [
                'table' => $table,
                'columns' => $columns,
                'types' => $types,
            ];
        } catch (\Throwable $exception) {
            if ($this->isPromptingEnabled()) {
                warning('Could not inspect database schema. Falling back to minimal defaults.');
            }

            return $fallback;
        }
    }

    /**
     * @param  array{table:string, columns:array<int, string>, types:array<string, string>}  $schema
     * @return array<int, string>
     */
    protected function resolveColumns(array $schema, string $componentClass, string $context): array
    {
        $optionColumns = $this->parseCsvOption((string) ($this->option('columns') ?? ''));

        if ($optionColumns !== []) {
            return $optionColumns;
        }

        $default = $this->defaultColumns($schema['columns'], $componentClass, $context);

        if (! $this->isPromptingEnabled()) {
            return $default;
        }

        return array_values(multiselect(
            label: 'Which columns should be included in the table?',
            options: $schema['columns'],
            default: $default,
            required: 'Choose at least one column.',
            hint: 'You can keep this broad and hide columns by default later.',
        ));
    }

    /**
     * @return array<int, string>
     */
    protected function parseCsvOption(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $value));
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));

        return array_values(array_unique($parts));
    }

    /**
     * @param  array<int, string>  $schemaColumns
     * @return array<int, string>
     */
    protected function defaultColumns(array $schemaColumns, string $componentClass, string $context): array
    {
        if ($schemaColumns === []) {
            return ['id'];
        }

        $preferred = ['id', 'first_name', 'last_name', 'name', 'email', 'created_at'];
        $defaults = array_values(array_intersect($preferred, $schemaColumns));

        if ($defaults === []) {
            $defaults = array_slice($schemaColumns, 0, min(count($schemaColumns), 8));
        }

        return $defaults;
    }

    /**
     * @param  array<int, string>  $allColumns
     * @param  array<string, string>  $columnTypes
     * @return array<int, string>
     */
    protected function resolveSearchableColumns(array $allColumns, array $columnTypes): array
    {
        $optionColumns = $this->parseCsvOption((string) ($this->option('searchable') ?? ''));

        if ($optionColumns !== []) {
            return array_values(array_intersect($allColumns, $optionColumns));
        }

        $default = array_values(array_filter($allColumns, fn (string $column): bool => $this->isTextColumn($column, $columnTypes[$column] ?? 'string')));

        if (! $this->isPromptingEnabled()) {
            return $default;
        }

        return array_values(multiselect(
            label: 'Which columns should be searchable?',
            options: $allColumns,
            default: $default,
            hint: 'Text columns are preselected by default.',
        ));
    }

    protected function isTextColumn(string $column, string $type): bool
    {
        if ($this->isEmailColumn($column)) {
            return true;
        }

        return in_array($type, ['string', 'text', 'mediumtext', 'longtext'], true);
    }

    protected function isEmailColumn(string $column): bool
    {
        return Str::contains($column, 'email');
    }

    /**
     * @param  array<int, string>  $allColumns
     * @param  array<string, string>  $columnTypes
     * @return array<int, string>
     */
    protected function resolveSortableColumns(array $allColumns, array $columnTypes): array
    {
        $optionColumns = $this->parseCsvOption((string) ($this->option('sortable') ?? ''));

        if ($optionColumns !== []) {
            return array_values(array_intersect($allColumns, $optionColumns));
        }

        $default = array_values(array_filter($allColumns, fn (string $column): bool => ! in_array($columnTypes[$column] ?? '', ['json', 'text', 'longtext'], true)));

        if ($default === [] && in_array('id', $allColumns, true)) {
            $default = ['id'];
        }

        if (! $this->isPromptingEnabled()) {
            return $default;
        }

        return array_values(multiselect(
            label: 'Which columns should be sortable?',
            options: $allColumns,
            default: $default,
            required: 'Choose at least one sortable column.',
        ));
    }

    /**
     * @param  array<int, string>  $allColumns
     * @return array<int, string>
     */
    protected function resolveVisibleColumns(array $allColumns): array
    {
        $optionColumns = $this->parseCsvOption((string) ($this->option('visible') ?? ''));

        if ($optionColumns !== []) {
            return array_values(array_intersect($allColumns, $optionColumns));
        }

        $default = array_slice($allColumns, 0, min(count($allColumns), 8));

        if (! $this->isPromptingEnabled()) {
            return $default;
        }

        return array_values(multiselect(
            label: 'Which columns should be visible by default?',
            options: $allColumns,
            default: $default,
            required: 'Choose at least one visible column.',
        ));
    }

    protected function tableDataKey(string $componentClass, string $context): string
    {
        $core = $this->componentCoreName($componentClass, $context);

        return Str::plural(Str::snake($core !== '' ? $core : 'items'));
    }

    protected function viewRelativeDirectory(string $context): string
    {
        return match ($context) {
            'shared' => 'components/tables',
            default => 'components/'.$context.'/tables',
        };
    }

    protected function relativePath(string $absolutePath): string
    {
        return Str::after($absolutePath, base_path().DIRECTORY_SEPARATOR);
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $sortableColumns
     * @param  array<string, string>  $columnTypes
     */
    protected function buildColumnDefinitionsCode(array $columns, array $sortableColumns, array $columnTypes): string
    {
        $lines = [];

        foreach ($columns as $column) {
            $definition = $this->columnDefinition($column, in_array($column, $sortableColumns, true), $columnTypes[$column] ?? 'string');
            $parts = [];

            foreach ($definition as $key => $value) {
                if (is_bool($value)) {
                    $parts[] = "'{$key}' => ".($value ? 'true' : 'false');

                    continue;
                }

                if (is_int($value)) {
                    $parts[] = "'{$key}' => {$value}";

                    continue;
                }

                $escaped = str_replace("'", "\\'", (string) $value);
                $parts[] = "'{$key}' => '{$escaped}'";
            }

            $lines[] = "'".$column."' => [".implode(', ', $parts).'],';
        }

        return implode("\n            ", $lines);
    }

    /**
     * @return array<string, string|bool|int>
     */
    protected function columnDefinition(string $column, bool $isSortable, string $type): array
    {
        $definition = [
            'label' => $this->columnLabel($column),
            'sortable' => $isSortable,
            'align' => 'left',
            'width' => 'min-w-40',
            'export_key' => $this->columnLabel($column),
        ];

        if ($this->isBooleanColumn($column, $type)) {
            $definition['align'] = 'center';
            $definition['formatter'] = 'yes_no';
            $definition['width'] = 'min-w-28';
        } elseif ($this->isDateTimeColumn($column, $type)) {
            $definition['formatter'] = 'date_time';
            $definition['width'] = 'min-w-40';
        } elseif ($this->isDateColumn($column, $type)) {
            $definition['formatter'] = 'date';
            $definition['width'] = 'min-w-36';
        } elseif ($this->isMoneyColumn($column, $type)) {
            $definition['align'] = 'right';
            $definition['formatter'] = 'money';
            $definition['width'] = 'min-w-40';
        } elseif ($this->isNumericColumn($type)) {
            $definition['align'] = 'right';
            $definition['width'] = 'min-w-28';
        } elseif ($this->isEmailColumn($column)) {
            $definition['tooltip'] = true;
            $definition['truncate'] = 52;
            $definition['width'] = 'min-w-56';
        } elseif ($this->isLongTextColumn($column, $type)) {
            $definition['tooltip'] = true;
            $definition['truncate'] = 48;
            $definition['width'] = 'min-w-60';
        }

        return $definition;
    }

    protected function columnLabel(string $column): string
    {
        if ($column === 'id') {
            return 'ID';
        }

        return (string) Str::of($column)->replace('_', ' ')->headline();
    }

    protected function isBooleanColumn(string $column, string $type): bool
    {
        return $type === 'boolean' || Str::startsWith($column, 'is_') || Str::startsWith($column, 'has_');
    }

    protected function isDateTimeColumn(string $column, string $type): bool
    {
        return in_array($type, ['datetime', 'timestamp'], true) || Str::endsWith($column, '_at');
    }

    protected function isDateColumn(string $column, string $type): bool
    {
        return $type === 'date' || Str::endsWith($column, '_date');
    }

    protected function isMoneyColumn(string $column, string $type): bool
    {
        if (! $this->isNumericColumn($type)) {
            return false;
        }

        return Str::contains($column, ['amount', 'total', 'sum', 'price', 'cost', 'invoice']);
    }

    protected function isNumericColumn(string $type): bool
    {
        return in_array($type, ['integer', 'bigint', 'smallint', 'decimal', 'float', 'double'], true);
    }

    protected function isLongTextColumn(string $column, string $type): bool
    {
        if (in_array($type, ['text', 'mediumtext', 'longtext'], true)) {
            return true;
        }

        return Str::contains($column, ['address', 'comment', 'note', 'description']);
    }

    /**
     * @param  array<int, string>  $sortableColumns
     */
    protected function buildSortColumnsCode(array $sortableColumns, string $table): string
    {
        if ($sortableColumns === []) {
            return "'id' => '{$table}.id',";
        }

        $lines = [];

        foreach ($sortableColumns as $column) {
            $lines[] = "'{$column}' => '{$table}.{$column}',";
        }

        return implode("\n            ", $lines);
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function buildStringListCode(array $columns): string
    {
        if ($columns === []) {
            return "'id',";
        }

        return implode("\n            ", array_map(
            fn (string $column): string => "'".$column."',",
            $columns,
        ));
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function buildApplySearchBody(array $columns): string
    {
        if ($columns === []) {
            return '';
        }

        $first = array_shift($columns);
        $conditions = ["\$builder->where('{$first}', 'like', \$search)"];

        foreach ($columns as $column) {
            $conditions[] = "->orWhere('{$column}', 'like', \$search)";
        }

        $conditionsCode = implode("\n                ", $conditions).';';

        return "        \$query->where(function (Builder \$builder) use (\$search): void {\n                {$conditionsCode}\n        });";
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function buildExportMethodsCode(bool $includeExport, array $columns): string
    {
        if (! $includeExport) {
            return '';
        }

        $filePrefix = $this->safeFilePrefix($columns);
        $labelMap = [];

        foreach ($columns as $column) {
            $labelMap[] = "            '".$this->columnLabel($column)."' => data_get(\$row, '{$column}'),";
        }

        $labelMapCode = implode("\n", $labelMap);

        return <<<PHP

    public function exportAll(string \$format): ?HttpResponse
    {
        \$rows = [];

        foreach (\$this->queryForTable(ignoreSearch: true)->get() as \$row) {
            \$rows[] = \$this->exportRow(\$row);
        }

        return \$this->exportRowsToDownload(\$rows, '{$filePrefix}_gesamt', \$format);
    }

    public function exportSelected(string \$format): ?HttpResponse
    {
        \$selectedIds = \$this->selectedIds();

        if (\$selectedIds === []) {
            \$this->toastNoSelection('Bitte wähle mindestens eine Zeile aus.');

            return null;
        }

        \$rows = [];

        foreach (\$this->baseQuery()->whereKey(\$selectedIds)->orderBy('id')->get() as \$row) {
            \$rows[] = \$this->exportRow(\$row);
        }

        return \$this->exportRowsToDownload(\$rows, '{$filePrefix}_auswahl', \$format);
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed \$row): array
    {
        return [
{$labelMapCode}
        ];
    }
PHP;
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function safeFilePrefix(array $columns): string
    {
        $base = $columns[0] ?? 'datatable';

        return Str::snake(Str::singular($base));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function renderStub(string $stubRelativePath, array $replacements): string
    {
        $stubPath = base_path($stubRelativePath);
        $contents = $this->files->get($stubPath);

        return str_replace(array_keys($replacements), array_values($replacements), $contents);
    }

    protected function writeFile(string $path, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }

    /**
     * @return array<string, string|array<int, string>|\Closure>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'context' => fn (): string => (string) select(
                label: 'Where will this datatable live?',
                options: [
                    'admin' => 'Admin backend',
                    'public' => 'Public pages',
                    'shared' => 'Reusable/shared',
                ],
                default: 'admin',
            ),
            'name' => fn (): string => (string) suggest(
                label: 'What is the component class name?',
                options: [
                    'AdminUsersTable',
                    'AdminDonationsTable',
                    'AdminPartnersTable',
                    'UsersTable',
                ],
                placeholder: 'E.g. AdminPartnersTable',
                required: true,
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Components;

use App\Components\Concerns\InteractsWithDatatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

abstract class AbstractDatatableComponent extends Component
{
    use InteractsWithDatatable;
    use WithPagination;

    #[Url]
    public string $sortField = '';

    public function mount(): void
    {
        if ($this->sortField === '') {
            $this->sortField = $this->defaultSortField();
        }

        $this->initializeVisibleColumns();
    }

    public function render(): View
    {
        $records = $this->queryForTable(ignoreSearch: false)->paginate($this->perPage);

        $this->hydrateTableState($records);

        return view($this->tableView(), array_merge(
            [
                $this->tableDataKey() => $records,
                'pageIds' => $this->pageIds($records),
            ],
            $this->tableViewData($records),
        ));
    }

    protected function queryForTable(bool $ignoreSearch): Builder
    {
        $this->normalizeSorting();

        $query = $this->baseQuery();
        $search = $this->normalizedSearchTerm($this->search);

        if (! $ignoreSearch && $search !== '') {
            $this->applySearch($query, $this->toEscapedLikePattern($search));
        }

        return $query->orderBy($this->resolvedSortColumn(), $this->sortDirection);
    }

    abstract protected function baseQuery(): Builder;

    protected function applySearch(Builder $query, string $search): void
    {
        $searchableColumns = $this->searchableColumns();

        if ($searchableColumns === []) {
            return;
        }

        $query->where(function (Builder $builder) use ($search, $searchableColumns): void {
            $isFirstCondition = true;

            foreach ($searchableColumns as $key => $column) {
                if (is_int($key)) {
                    if ($column === '') {
                        continue;
                    }

                    $this->applySearchColumnCondition(
                        builder: $builder,
                        column: $column,
                        search: $search,
                        boolean: $isFirstCondition ? 'and' : 'or',
                    );

                    $isFirstCondition = false;

                    continue;
                }

                if ($column === '') {
                    continue;
                }

                $builder->whereRaw(
                    sql: $column." like ? escape '\\'",
                    bindings: [$search],
                    boolean: $isFirstCondition ? 'and' : 'or',
                );

                $isFirstCondition = false;
            }
        });
    }

    /**
     * @return array<int|string, string>
     */
    abstract protected function searchableColumns(): array;

    protected function normalizedSearchTerm(string $search): string
    {
        $trimmedSearch = trim($search);

        if ($trimmedSearch === '') {
            return '';
        }

        $sanitizedSearch = Str::of($trimmedSearch)
            ->replaceMatches('/[[:cntrl:]]/u', '')
            ->squish()
            ->toString();

        if ($sanitizedSearch === '') {
            return '';
        }

        return mb_substr($sanitizedSearch, 0, 120);
    }

    protected function toEscapedLikePattern(string $search): string
    {
        $escapedSearch = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $search,
        );

        return '%'.$escapedSearch.'%';
    }

    protected function applySearchColumnCondition(Builder $builder, string $column, string $search, string $boolean): void
    {
        if (str_contains($column, '.')) {
            $relation = (string) Str::beforeLast($column, '.');
            $relationColumn = (string) Str::afterLast($column, '.');

            throw_if($relation === '' || $relationColumn === '', \LogicException::class, static::class.sprintf(' has an invalid searchable relation column [%s].', $column));

            $callback = function (Builder $relationQuery) use ($relationColumn, $search): void {
                $this->applySearchColumnCondition(
                    builder: $relationQuery,
                    column: $relationColumn,
                    search: $search,
                    boolean: 'and',
                );
            };

            if ($boolean === 'or') {
                $builder->orWhereHas($relation, $callback);

                return;
            }

            $builder->whereHas($relation, $callback);

            return;
        }

        throw_unless(preg_match('/^[A-Za-z_]\w*$/', $column), \LogicException::class, static::class.sprintf(' has an invalid searchable column [%s].', $column));

        $wrappedColumn = $builder->getQuery()->getGrammar()->wrap($column);

        $builder->whereRaw(
            sql: $wrappedColumn." like ? escape '\\'",
            bindings: [$search],
            boolean: $boolean,
        );
    }

    protected function resolvedSortColumn(): string
    {
        return $this->sortColumns()[$this->sortField];
    }

    protected function normalizeSorting(): void
    {
        if (! $this->isAllowedSortField($this->sortField)) {
            $this->sortField = $this->defaultSortField();
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'asc';
        }
    }

    protected function isAllowedSortField(string $field): bool
    {
        return array_key_exists($field, $this->sortColumns());
    }

    protected function defaultSortField(): string
    {
        $defaultSortField = array_search($this->defaultSortColumn(), $this->sortColumns(), true);

        throw_if(! is_string($defaultSortField) || $defaultSortField === '', \LogicException::class, static::class.' must map defaultSortColumn() in sortColumns().');

        return $defaultSortField;
    }

    /**
     * @return array<string, string>
     */
    abstract protected function sortColumns(): array;

    abstract protected function defaultSortColumn(): string;

    protected function hydrateTableState(LengthAwarePaginator $paginator): void {}

    abstract protected function tableView(): string;

    abstract protected function tableDataKey(): string;

    /**
     * @return array<int, int>
     */
    protected function pageIds(LengthAwarePaginator $paginator): array
    {
        return $paginator->getCollection()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function tableViewData(LengthAwarePaginator $paginator): array
    {
        return [];
    }
}

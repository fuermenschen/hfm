<?php

namespace App\Components;

use App\Components\Concerns\InteractsWithAdminDatatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

abstract class AbstractAdminDatatableComponent extends Component
{
    use InteractsWithAdminDatatable;
    use WithPagination;

    public string $sortField = '';

    public function mount(): void
    {
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
        $search = trim($this->search);

        if (! $ignoreSearch && $search !== '') {
            $this->applySearch($query, '%'.$search.'%');
        }

        return $query->orderBy($this->resolvedSortColumn(), $this->sortDirection);
    }

    abstract protected function baseQuery(): Builder;

    abstract protected function applySearch(Builder $query, string $search): void;

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

        if (! is_string($defaultSortField) || $defaultSortField === '') {
            throw new \LogicException(static::class.' must map defaultSortColumn() in sortColumns().');
        }

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

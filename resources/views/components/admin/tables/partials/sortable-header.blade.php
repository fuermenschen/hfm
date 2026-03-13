@if ($this->isColumnSortable($column))
    <button type="button" wire:click="sortByColumn('{{ $column }}')" class="inline-flex cursor-pointer items-center gap-1">
        <span>{{ $label }}</span>

        @if ($this->sortIndicator($column) === 'asc')
            <flux:icon.arrow-up class="size-3.5 text-zinc-500" />
        @elseif ($this->sortIndicator($column) === 'desc')
            <flux:icon.arrow-down class="size-3.5 text-zinc-500" />
        @else
            <flux:icon.arrows-up-down class="size-3.5 text-zinc-400" />
        @endif
    </button>
@else
    <span>{{ $label }}</span>
@endif

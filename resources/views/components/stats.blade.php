@props(['title', 'columns' => 4])

@php($columnsClass = match ($columns) {
    5 => 'sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5',
    6 => 'sm:grid-cols-3 lg:grid-cols-6 xl:grid-cols-6',
    default => 'sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4',
})

<div class="mt-9 first:mt-0">
    <flux:heading size="xl">{{ $title }}</flux:heading>
    <dl class="mt-5 grid grid-cols-1 gap-5 {{ $columnsClass }}">{{ $slot }}</dl>
</div>

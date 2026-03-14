@props(['header' => false, 'columnClass' => 'right-0 w-14 bg-[var(--color-base-50)] dark:bg-[var(--color-base-800)]'])

@if ($header)
    <flux:table.column sticky class="{{ $columnClass }}">
        <div class="flex h-full w-full items-center justify-center">
            <flux:icon.cog-6-tooth class="size-4" />
        </div>
    </flux:table.column>
@else
    <flux:table.cell sticky class="{{ $columnClass }}">
        <div class="flex h-full w-full items-center justify-center">
            {{ $slot }}
        </div>
    </flux:table.cell>
@endif

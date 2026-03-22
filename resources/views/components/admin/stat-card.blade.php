<a
    href="{{ route($route) }}" wire:navigate.hover
    class="overflow-hidden rounded-lg bg-base-50 px-4 py-5 shadow sm:p-6 dark:bg-base-800">
    <dt class="truncate text-sm font-medium">{{ $title }}</dt>
    <dd class="mt-1 text-3xl font-semibold tracking-tight">{{ $value }}</dd>
</a>

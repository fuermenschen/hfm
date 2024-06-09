<a
    href="{{ route($route) }}" wire:navigate.hover
    class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 dark:bg-slate-600">
    <dt class="truncate text-sm font-medium text-hfm-dark/50 dark:text-hfm-white/60">{{ $title }}</dt>
    <dd class="mt-1 text-3xl font-semibold tracking-tight text-hfm-dark dark:text-hfm-white">{{ $value }}</dd>
</a>

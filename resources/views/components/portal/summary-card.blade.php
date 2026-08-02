@props(['href', 'icon', 'label', 'value', 'detail'])

<a href="{{ $href }}" wire:navigate class="block rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hfm-red">
    <flux:card class="h-full space-y-3 rounded-xl border-hfm-light/50 bg-gradient-to-br from-hfm-light/20 to-white shadow-sm dark:border-slate-700 dark:from-slate-800 dark:to-slate-900">
        <flux:icon :name="$icon" class="size-6 text-hfm-dark dark:text-hfm-light" />
        <div>
            <flux:text>{{ $label }}</flux:text>
            <flux:heading size="xl" class="mt-1 tabular-nums">{{ $value }}</flux:heading>
        </div>
        <flux:text>{{ $detail }}</flux:text>
    </flux:card>
</a>

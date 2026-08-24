@props(['href', 'icon', 'label', 'value', 'detail'])

<a
    href="{{ $href }}"
    wire:navigate
    class="focus-visible:outline-hfm-red block rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2"
>
    <flux:card class="border-hfm-light/50 from-hfm-light/20 h-full space-y-3 rounded-xl bg-gradient-to-br to-white shadow-sm dark:border-slate-700 dark:from-slate-800 dark:to-slate-900">
        <flux:icon :name="$icon" class="text-hfm-dark dark:text-hfm-light size-6" />
        <div>
            <flux:text>{{ $label }}</flux:text>
            <flux:heading size="xl" class="mt-1 tabular-nums">{{ $value }}</flux:heading>
        </div>
        <flux:text>{{ $detail }}</flux:text>
    </flux:card>
</a>

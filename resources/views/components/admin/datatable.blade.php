<div class="min-w-0 max-w-full space-y-4">
    @isset($toolbar)
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-3">
            {{ $toolbar }}
        </div>
    @endisset

    <flux:card class="max-w-full overflow-hidden">
        <div class="relative z-10 w-full max-w-full overflow-x-auto">
            {{ $slot }}
        </div>
    </flux:card>

    @isset($footer)
        <div class="flex min-w-0 flex-wrap items-center justify-start gap-3">
            {{ $footer }}
        </div>
    @endisset
</div>

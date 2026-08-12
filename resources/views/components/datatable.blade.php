<div class="min-w-0 max-w-full space-y-4">
    @isset($toolbar)
        <div class="min-w-0">
            {{ $toolbar }}
        </div>
    @endisset

    <flux:card class="max-w-full overflow-hidden">
        <div class="relative z-10 w-full max-w-full overflow-x-auto">
            {{ $slot }}
        </div>
    </flux:card>

    @isset($footer)
        <div class="flex min-w-0 flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between [&>[data-flux-pagination]]:w-full sm:[&>[data-flux-pagination]]:min-w-0 sm:[&>[data-flux-pagination]]:flex-1">
            {{ $footer }}
        </div>
    @endisset
</div>

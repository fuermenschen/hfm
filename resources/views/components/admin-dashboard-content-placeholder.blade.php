<div class="mt-9 space-y-9" aria-busy="true" aria-label="Statistiken werden geladen">
    <flux:text class="sr-only">Statistiken werden nach dem Laden der Seite berechnet.</flux:text>
    <div class="animate-pulse space-y-9" aria-hidden="true">
        <div class="h-64 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"></div>

        @foreach ([1, 2, 3] as $section)
            <div wire:key="dashboard-placeholder-section-{{ $section }}" class="space-y-4">
                <div class="h-7 w-48 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="h-24 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"></div>
                    <div class="h-24 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"></div>
                    <div class="h-24 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"></div>
                    <div class="h-24 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

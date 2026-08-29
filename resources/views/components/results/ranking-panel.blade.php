@props([
    'title',
    'entries',
])

<div class="rounded-2xl bg-zinc-100 p-3 sm:p-6 dark:bg-zinc-900">
    <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">{{ $title }}</p>
    <ol class="mt-2 space-y-1.5 sm:mt-4 sm:space-y-2">
        @forelse ($entries as $index => $entry)
            <li class="flex items-baseline gap-3 border-b border-zinc-200 pb-1.5 sm:pb-2 dark:border-zinc-800">
                <span class="w-5 shrink-0 text-xs font-semibold text-zinc-400 tabular-nums sm:w-6 sm:text-sm">{{ $index + 1 }}.</span>
                <span class="min-w-0 flex-1 truncate text-base sm:text-lg">{{ $entry['name'] }}</span>
                <span class="text-base font-semibold tabular-nums sm:text-lg">Fr. {{ number_format($entry['amount'], 0, '.', "'") }}</span>
            </li>
        @empty
            <li class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">Noch keine Spenden erfasst.</li>
        @endforelse
    </ol>
</div>

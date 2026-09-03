@props([
    'title',
    'metric',
    'entries',
])

<div class="flex h-full min-h-0 flex-col rounded-2xl bg-zinc-100 p-3 sm:p-6 dark:bg-zinc-900">
    <div class="flex items-baseline justify-between gap-3">
        <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">{{ $title }}</p>
        <p class="text-xs font-semibold text-zinc-500 sm:text-sm dark:text-zinc-400">{{ $metric }}</p>
    </div>
    <ol class="[&::-webkit-scrollbar]:hidden mt-2 min-h-0 flex-1 [scrollbar-width:none] space-y-1.5 overflow-y-auto sm:mt-4 sm:space-y-2">
        @forelse ($entries as $index => $entry)
            <li class="flex items-baseline gap-3 border-b border-zinc-200 pb-1.5 sm:pb-2 dark:border-zinc-800">
                <span class="w-5 shrink-0 text-xs font-semibold text-zinc-400 tabular-nums sm:w-6 sm:text-sm">{{ $index + 1 }}.</span>
                <span class="min-w-0 flex-1 truncate text-base sm:text-lg">{{ $entry['name'] }}</span>
                <span class="text-base font-semibold tabular-nums sm:text-lg">
                    @if ($metric === 'Spenden')
                        Fr. {{ number_format($entry['value'], 0, '.', "'") }}
                    @elseif ($metric === 'Höhenmeter')
                        {{ number_format($entry['value'], 0, '.', "'") }} m
                    @else
                        {{ number_format($entry['value'], 0, '.', "'") }}
                    @endif
                </span>
            </li>
        @empty
            <li class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">Noch keine Resultate erfasst.</li>
        @endforelse
    </ol>
</div>

<div
    wire:poll.15s
    class="bg-hfm-white text-hfm-dark dark:bg-hfm-dark dark:text-hfm-white flex h-screen flex-col overflow-hidden px-3 py-4 sm:px-10 sm:py-8"
>
    @if (! ($totals['has_event'] ?? false))
        <div class="flex flex-1 items-center justify-center">
            <p class="text-3xl text-zinc-500 dark:text-zinc-400">Aktuell ist kein Anlass aktiv.</p>
        </div>
    @else
        <header class="flex shrink-0 flex-wrap items-baseline justify-between gap-2 sm:gap-4">
            <h1 class="text-xl font-bold tracking-tight sm:text-4xl">{{ $totals['event_title'] }}</h1>
            <span class="flex items-center gap-2 text-xs text-zinc-500 sm:text-sm xl:text-lg dark:text-zinc-400">
                <span class="inline-block size-2 animate-pulse rounded-full bg-red-500 xl:size-3"></span>
                Live
            </span>
        </header>

        <div class="mt-4 grid shrink-0 auto-rows-min grid-cols-2 gap-3 sm:mt-8 sm:gap-5 lg:grid-cols-4">
            <div class="col-span-2 rounded-2xl bg-zinc-100 p-3 sm:p-6 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">Total Spenden</p>
                <p class="mt-1 text-2xl font-bold tracking-tight tabular-nums sm:mt-2 sm:text-4xl lg:text-6xl">
                    Fr. {{ number_format($totals['donations_total'], 0, '.', "'") }}
                </p>
            </div>

            <div class="rounded-2xl bg-zinc-100 p-3 sm:p-6 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">Absolvierte Runden</p>
                <p class="mt-1 text-2xl font-bold tracking-tight tabular-nums sm:mt-2 sm:text-4xl lg:text-5xl">
                    {{ number_format($totals['rounds'], 0, '.', "'") }}
                </p>
            </div>

            <div class="rounded-2xl bg-zinc-100 p-3 sm:p-6 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">Höhenmeter</p>
                <p class="mt-1 text-2xl font-bold tracking-tight tabular-nums sm:mt-2 sm:text-4xl lg:text-5xl">
                    {{ number_format($totals['elevation_m'], 0, '.', "'") }}<span
                        class="text-base text-zinc-500 sm:text-xl dark:text-zinc-400"
                    >
                        m</span>
                </p>
            </div>
        </div>

        <div class="mt-4 shrink-0 sm:mt-6">
            @if (count($totals['per_partner']) > 0)
                <ul class="[&::-webkit-scrollbar]:hidden grid max-h-[25vh] [scrollbar-width:none] grid-cols-3 gap-2 overflow-y-auto sm:gap-3">
                    @foreach ($totals['per_partner'] as $partner)
                        <li class="rounded-2xl bg-zinc-100 p-2 sm:p-4 dark:bg-zinc-900">
                            <p
                                class="truncate text-xs text-zinc-500 sm:text-sm dark:text-zinc-400"
                                title="{{ $partner['name'] }}"
                            >
                                {{ $partner['name'] }}
                            </p>
                            <p class="mt-1 truncate text-xl font-bold tracking-tight tabular-nums sm:mt-2 sm:text-4xl lg:text-5xl">
                                Fr. {{ number_format($partner['amount'], 0, '.', "'") }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <x-results.rankings :rankings="$totals['rankings']" />

        <footer class="mt-3 shrink-0 text-xs text-zinc-500 sm:mt-4 dark:text-zinc-400">
            Spendenangaben basieren auf der Annahme, dass alle Rechnungen beglichen werden. Abweichungen möglich.
        </footer>
    @endif
</div>

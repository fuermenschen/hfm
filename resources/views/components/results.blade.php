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

        <div class="[&::-webkit-scrollbar]:hidden mt-4 min-h-0 flex-1 [scrollbar-width:none] overflow-y-auto sm:mt-6">
            <div class="lg:hidden">
                <flux:carousel autoplay autoplay:interval="10000" wrap="rewind">
                    <flux:carousel.slide class="w-full">
                        <x-results.ranking-panel
                            title="Rangliste Sportler:innen"
                            :entries="$totals['athlete_ranking']"
                        />
                    </flux:carousel.slide>
                    <flux:carousel.slide class="w-full">
                        <x-results.ranking-panel title="Rangliste Gruppen" :entries="$totals['group_ranking']" />
                    </flux:carousel.slide>
                </flux:carousel>
            </div>

            <div class="hidden gap-5 lg:grid lg:grid-cols-2">
                <x-results.ranking-panel title="Rangliste Sportler:innen" :entries="$totals['athlete_ranking']" />
                <x-results.ranking-panel title="Rangliste Gruppen" :entries="$totals['group_ranking']" />
            </div>
        </div>

        <div class="mt-4 shrink-0 sm:mt-6">
            @if (count($totals['per_partner']) > 0)
                <p class="text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">Spenden pro Benefizpartner:in</p>
                <ul class="[&::-webkit-scrollbar]:hidden mt-2 grid max-h-[25vh] [scrollbar-width:none] grid-cols-2 gap-3 overflow-y-auto sm:mt-3 sm:grid-cols-3">
                    @foreach ($totals['per_partner'] as $partnerName => $amount)
                        <li class="rounded-2xl bg-zinc-100 p-3 sm:p-4 dark:bg-zinc-900">
                            <p
                                class="truncate text-xs text-zinc-500 sm:text-sm dark:text-zinc-400"
                                title="{{ $partnerName }}"
                            >
                                {{ $partnerName }}
                            </p>
                            <p class="mt-1 truncate text-lg font-bold tabular-nums sm:text-xl">
                                Fr. {{ number_format($amount, 0, '.', "'") }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <footer class="mt-3 text-xs text-zinc-500 sm:mt-4 dark:text-zinc-400">
                Spendenangaben basieren auf der Annahme, dass alle Rechnungen beglichen werden. Abweichungen möglich.
            </footer>
        </div>
    @endif
</div>

@props(['rankings'])

@php
    $metrics = [
        'donations' => 'Spenden',
        'rounds' => 'Runden',
        'elevation_m' => 'Höhenmeter',
    ];
@endphp

<div
    x-data="{
        desktopMetric: 0,
        mobileView: 0,
        metricCount: {{ count($metrics) }},
        timer: null,
        init() {
            this.timer = setInterval(() => {
                this.desktopMetric = (this.desktopMetric + 1) % this.metricCount;
                this.mobileView = (this.mobileView + 1) % (this.metricCount * 2);
            }, 10000);
        },
        destroy() {
            clearInterval(this.timer);
        },
    }"
    class="mt-4 min-h-0 flex-1 sm:mt-6"
>
    <div class="hidden h-full lg:block">
        @foreach ($metrics as $key => $label)
            <div x-cloak x-show="desktopMetric === {{ $loop->index }}" class="grid h-full grid-cols-2 gap-5">
                <x-results.ranking-panel
                    title="Rangliste Sportler:innen"
                    :metric="$key"
                    :label="$label"
                    :entries="$rankings['athletes'][$key]"
                />
                <x-results.ranking-panel
                    title="Rangliste Gruppen"
                    :metric="$key"
                    :label="$label"
                    :entries="$rankings['groups'][$key]"
                />
            </div>
        @endforeach
    </div>

    <div class="h-full lg:hidden">
        @foreach ($metrics as $key => $label)
            <div x-cloak x-show="mobileView === {{ $loop->index * 2 }}" class="h-full">
                <x-results.ranking-panel
                    title="Rangliste Sportler:innen"
                    :metric="$key"
                    :label="$label"
                    :entries="$rankings['athletes'][$key]"
                />
            </div>
            <div x-cloak x-show="mobileView === {{ $loop->index * 2 + 1 }}" class="h-full">
                <x-results.ranking-panel
                    title="Rangliste Gruppen"
                    :metric="$key"
                    :label="$label"
                    :entries="$rankings['groups'][$key]"
                />
            </div>
        @endforeach
    </div>
</div>

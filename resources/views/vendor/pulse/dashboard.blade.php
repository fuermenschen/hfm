@use('Laravel\Pulse\Facades\Pulse')

@once
    @push('head')
        {!! Pulse::css() !!}
    @endpush
@endonce

@component('layouts.admin', ['title' => 'Pulse'])

    @section('content')
        <div class="mb-6 flex flex-wrap items-center justify-end gap-3">
            <div class="text-sm text-slate-500 dark:text-slate-300">Zeitraum</div>
            <div class="flex items-center gap-2">
                @php($current = request('period'))
                @php($periods = [
                    '1_hour' => '1h',
                    '6_hours' => '6h',
                    '24_hours' => '24h',
                    '7_days' => '7T',
                ])
                @foreach($periods as $value => $label)
                    <a href="{{ route('pulse', array_filter(['period' => $value])) }}"
                       @class([
                           'inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium ring-1 ring-inset transition',
                           'bg-slate-700 text-white ring-slate-600' => $current === $value || (!$current && $value === '1_hour'),
                           'bg-slate-100 text-slate-700 ring-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700' => !($current === $value || (!$current && $value === '1_hour')),
                       ])
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <livewire:pulse.usage rows="2" />

            <livewire:pulse.queues />

            <livewire:pulse.cache />

            <livewire:pulse.slow-queries />

            <livewire:pulse.exceptions />

            <livewire:pulse.slow-requests />

            <livewire:pulse.slow-jobs />

            <livewire:pulse.slow-outgoing-requests cols="6" />
        </div>
    @endsection

@endcomponent

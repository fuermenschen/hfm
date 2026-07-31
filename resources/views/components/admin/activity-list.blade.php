@props(['title', 'activities'])

<div class="mt-9 max-w-xl">
    <flux:heading size="xl">{{ $title }}</flux:heading>

    @if ($activities === [])
        <flux:text class="mt-5">Keine Aktivitäten in den letzten sieben Tagen.</flux:text>
    @else
        <flux:timeline align="start" class="mt-7">
            @foreach ($activities as $activity)
            <x-admin.activity-list-entry :activity="$activity" />
            @endforeach
        </flux:timeline>
    @endif
</div>

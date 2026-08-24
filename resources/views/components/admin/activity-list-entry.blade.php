@props(['activity'])

@php
    [$activityText, $activityIcon] = match ($activity['type']) {
        'external_user' => [$activity['name'].' wurde als externe Person registriert.', 'user'],
        'athlete_registration' => [$activity['name'].' hat sich als Sportler:in registriert.', 'user-plus'],
        'donation' => [$activity['name'].' hat eine Spende für '.$activity['name2'].' eingetragen.', 'banknotes'],
    };

    $createdAt = $activity['created_at'];
@endphp

<flux:timeline.item align="start">
    <flux:timeline.indicator>
        <flux:icon :name="$activityIcon" variant="micro" />
    </flux:timeline.indicator>
    <flux:timeline.content>
        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
            <flux:text>{{ $activityText }}</flux:text>
            <flux:text class="text-sm whitespace-nowrap">
                <time datetime="{{ $createdAt->toIso8601String() }}">{{ $createdAt->format('d.m.Y H:i') }}</time>
            </flux:text>
        </div>
    </flux:timeline.content>
</flux:timeline.item>

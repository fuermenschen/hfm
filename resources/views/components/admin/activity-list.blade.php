@props(['title', 'activities'])

<div class="mt-9 max-w-xl">
    <h2 class="text-xl font-semibold leading-6">{{ $title }}</h2>

    <ul role="list" class="mt-9">
        @foreach ($activities as $activity)
            <x-admin.activity-list-entry :activity="$activity" />
        @endforeach
    </ul>
</div>

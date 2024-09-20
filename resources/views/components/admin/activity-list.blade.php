@props(['title', 'activities'])

<div class="mt-md max-w-xl">
    <h2 class="text-xl font-semibold leading-6 text-hfm-dark dark:text-hfm-white">{{ $title }}</h2>

    <ul role="list" class="mt-md">
        @foreach ($activities as $activity)
            <x-admin.activity-list-entry :activity="$activity" />
        @endforeach
    </ul>
</div>

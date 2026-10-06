@props(['events'])

@if ($events->isNotEmpty())
    <section class="mx-auto my-12 max-w-3xl">
        <h2 class="text-2xl font-bold">Vergangene Anlässe</h2>
        <ul class="mt-6 space-y-4">
            @foreach ($events as $event)
                <li>
                    <p class="font-semibold">
                        {{ $event->title }} ·
                        <time datetime="{{ $event->starts_at->toDateString() }}">{{ $event->starts_at->translatedFormat('j. F Y') }}</time>
                    </p>
                    <x-inline-link href="{{ route('questions-and-answers.show', ['donationEvent' => $event->slug]) }}">Fragen und Antworten</x-inline-link>
                    ·
                    <x-inline-link href="{{ route('results.show', ['donationEvent' => $event->slug]) }}">Resultate</x-inline-link>
                </li>
            @endforeach
        </ul>
    </section>
@endif

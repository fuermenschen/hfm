@extends('layouts.public')
@php use Illuminate\Support\Str; @endphp
@section('content')
    <div>
        <x-page-title>Fragen und Antworten</x-page-title>
        <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
            Auf dieser Seite findest du alle wichtigen Informationen rund um den Spendenlauf &laquo;<strong
                >Höhenmeter für Menschen</strong
            >&raquo;. Sollte dennoch etwas unklar sein,
            <x-inline-link href=" {{ route('contact') }}">schreib uns</x-inline-link>
            !
        </div>
        <div class="mx-auto my-12 grid max-w-lg grid-cols-2 gap-x-3 gap-y-3 sm:grid-cols-4 sm:gap-y-2">
            <flux:button href="#allgemein" variant="filled" size="xs">Allgemein</flux:button>
            <flux:button href="#sportlerinnen" variant="filled" size="xs">Sportler:innen</flux:button>
            <flux:button href="#spenderinnen" variant="filled" size="xs">Spender:innen</flux:button>
            <flux:button href="#hintergruende" variant="filled" size="xs">Hintergründe</flux:button>
        </div>

        @if ($currentDonationEvent === null)
            <div class="mx-auto mt-2 w-full max-w-3xl rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-600/60 dark:bg-amber-950/40 dark:text-amber-100">
                Aktuell ist kein Anlass als aktiv veröffentlicht. Allgemeine Informationen auf dieser Seite bleiben
                korrekt, anlassbezogene Angaben können jedoch fehlen oder nicht aktuell sein.
            </div>
        @endif
    </div>

    @php
        $faqSections = [
            ['id' => 'allgemein', 'title' => 'Allgemein', 'group' => 'general'],
            ['id' => 'sportlerinnen', 'title' => 'Sportler:innen', 'group' => 'athletes'],
            ['id' => 'spenderinnen', 'title' => 'Spender:innen', 'group' => 'donors'],
            ['id' => 'hintergruende', 'title' => 'Hintergründe', 'group' => 'background'],
        ];

        $faqsByGroup = $currentEventFaqs->groupBy('group_name');
    @endphp

    @foreach ($faqSections as $section)
        <x-page-subtitle :id="$section['id']">{{ $section['title'] }}</x-page-subtitle>

        <dl class="mb-9 space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30">
            @forelse (($faqsByGroup[$section['group']] ?? collect())->sortBy('group_sort_order') as $faq)
                <x-faq-question-answer>
                    <x-slot:question>{{ $faq->title }}</x-slot:question>
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {!!
                            Str::markdown((string) $faq->content_md, [
                                'html_input' => 'strip',
                                'allow_unsafe_links' => false,
                            ])
                        !!}
                    </div>
                </x-faq-question-answer>
            @empty
                <x-faq-question-answer>
                    <x-slot:question>Information folgt</x-slot:question>
                    Für diesen Bereich sind aktuell noch keine Einträge vorhanden.
                </x-faq-question-answer>
            @endforelse
        </dl>
    @endforeach
@endsection

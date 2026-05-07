@extends('layouts.public')
@php use Illuminate\Support\Str; @endphp
@section('content')

    <div>
        <x-page-title>Fragen und Antworten</x-page-title>
        <div
            class="w-full max-w-2xl mx-auto text-left sm:text-center">Auf dieser Seite findest du alle wichtigen
            Informationen rund um den
            Spendenlauf &laquo;<strong>Höhenmeter für
                Menschen</strong>&raquo;. Sollte dennoch etwas unklar sein,
            <x-inline-link href=" {{ route('contact') }}">schreib uns</x-inline-link>
            !
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-3 gap-x-3 sm:gap-y-2 my-12 mx-auto max-w-lg">
            <flux:button
                href="#allgemein"
                variant="filled"
                size="xs"
            >Allgemein</flux:button>
            <flux:button
                href="#sportlerinnen"
                variant="filled"
                size="xs"
            >Sportler:innen</flux:button>
            <flux:button
                href="#spenderinnen"
                variant="filled"
                size="xs"
            >Spender:innen</flux:button>
            <flux:button
                href="#hintergruende"
                variant="filled"
                size="xs"
            >Hintergründe</flux:button>
        </div>

        @if ($currentDonationEvent === null)
            <div class="mx-auto mt-2 w-full max-w-3xl rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-600/60 dark:bg-amber-950/40 dark:text-amber-100">
                Aktuell ist kein Anlass als aktiv veröffentlicht. Allgemeine Informationen auf dieser Seite bleiben korrekt, anlassbezogene Angaben können jedoch fehlen oder nicht aktuell sein.
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

        <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30 mb-9">
            @forelse (($faqsByGroup[$section['group']] ?? collect())->sortBy('group_sort_order') as $faq)
                <x-faq-question-answer>
                    <x-slot:question>{{ $faq->title }}</x-slot:question>
                    <div class="max-w-none prose prose-p:my-0 dark:prose-invert">
                        {!! Str::markdown((string) $faq->content_md, [
                            'html_input' => 'strip',
                            'allow_unsafe_links' => false,
                        ]) !!}
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

@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Kontakt
    @endcomponent

    <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
        Hast du eine Frage oder ein Anliegen? Melde dich gerne bei uns, wir freuen uns auf deine Nachricht! Du kannst
        uns auch direkt eine E-Mail schreiben an
        <a
            class="text-hfm-red hover:text-hfm-light underline"
            href="mailto:{{ config('mail.from.address') }}"
        >{{ config('mail.from.address') }}</a>
        .
    </div>

    @livewire('contact-form')

@endsection

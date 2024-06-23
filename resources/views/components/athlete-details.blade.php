<div>
    <x-page-title>Hallo {{ $athlete['first_name'] }}</x-page-title>

    <div
        class="w-full max-w-2xl mx-auto text-left sm:text-center space-y-md">
        <p>Auf dieser Seite siehst du, wer sich schon
            alles als
            Spender:in für dich eingetragen hat. Du kannst den Link in der Mail jederzeit wieder aufrufen, um zu sehen,
            wer
            sich schon alles für dich registriert hat.</p>
        <p>
            Zudem kannst du personalisierte Bilder herunterladen, die du auf deinen Social-Media-Kanälen teilen kannst.
        </p>
    </div>


    <div class="w-full max-w-xl mx-auto">
        <x-page-subtitle>Bilder für Social Media</x-page-subtitle>
        <p class="">Hier kannst du personalisierte Bilder herunterladen, die du auf deinen Social-Media-Kanälen
            teilen kannst.</p>

        <x-page-subsubtitle>Für Insta- und Whatsapp-Storys</x-page-subsubtitle>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-sm">
            <div class="flex flex-col gap-y-2 items-center">
                <img src="{{ Vite::asset('resources/image_templates/story_single_light.jpg') }}" alt="Athlete Light"
                     class="w-32 object-contain border-2">
                <x-button wire:click="downloadStorySingleLight" spinner="downloadStorySingleLight" xs>Herunterladen
                </x-button>
            </div>
            <div class="flex flex-col gap-y-2 items-center">
                <img src="{{ Vite::asset('resources/image_templates/story_single_dark.jpg') }}" alt="Athlete Light"
                     class="w-32 object-contain border-2">
                <x-button wire:click="downloadStorySingleDark" spinner="downloadStorySingleDark" xs>Herunterladen
                </x-button>
            </div>

        </div>

        <x-page-subtitle>Spender:innen</x-page-subtitle>
        @if ($donations->count() > 0)
            <ul role="list" class="divide-y divide-gray-900/10 dark:divide-gray-100/30">
                @foreach ($donations as $donation)
                    <li class="flex justify-between gap-x-6 py-5">
                        <div class="flex min-w-0 gap-x-4">
                            <div class="min-w-0 flex-auto">
                                <p class="text-sm font-semibold leading-6">{{ $donation['donator'] }}</p>
                                @if (!$donation['verified'])
                                    <p class="mt-1 truncate text-xs leading-5 text-hfm-red">nicht verifiziert</p>
                                @else
                                    <p class="mt-1 truncate text-xs leading-5 text-gray-500">verifiziert</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <p class="text-sm leading-6">
                                Fr. {{ sprintf('%1.2f',$donation['amount_per_round']) }} pro Runde</p>
                            <p class="mt-1 text-xs leading-5 text-gray-500">

                                @php
                                    if ($donation['amount_min'] && $donation['amount_max']) {
                                        $min_max_text = "Fr. " . sprintf('%1.2f',$donation['amount_min']) . " bis Fr. " . sprintf('%1.2f',$donation['amount_max']);
                                    } elseif ($donation['amount_min']) {
                                        $min_max_text = "mindestens Fr. " . sprintf('%1.2f',$donation['amount_min']);
                                    } elseif ($donation['amount_max']) {
                                        $min_max_text = "maximal Fr. " . sprintf('%1.2f',$donation['amount_max']);
                                    }else {
                                        $min_max_text = "unbegrenzt";
                                    }
                                @endphp
                                {{ $min_max_text }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p>Es hat sich noch niemand als Spender:in eingetragen.</p>
        @endif
    </div>
</div>

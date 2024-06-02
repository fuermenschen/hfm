<div>
    <x-page-title>Hallo {{ $athlete['first_name'] }}</x-page-title>

    <div
        class="w-full max-w-2xl mx-auto text-left sm:text-center">Auf dieser Seite siehst du, wer sich schon alles als
        Spender:in für dich eingetragen hat. Du kannst den Link in der Mail jederzeit wieder aufrufen, um zu sehen, wer
        sich schon alles für dich registriert hat.
    </div>


    <div class="w-full max-w-xl mx-auto">
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
                                Fr. {{ sprintf('%1.2f',$donation['amount_min']) }} bis
                                Fr. {{ sprintf('%1.2f',$donation['amount_max']) }}
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

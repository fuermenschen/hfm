<div>
    <x-page-title>Hallo {{ $athlete['first_name'] }}</x-page-title>

    <p>Auf dieser Seite siehst du, wer sich bei dir als Spender:in eingetragen hat.</p>

    <x-page-subtitle>Spender:innen</x-page-subtitle>
    @if ($donations->count() > 0)
        <ul>
            @foreach ($donations as $donation)
                <li class="list-disc">
                    {{ $donation['donator'] }}
                    @if (!$donation['verified'])
                        <span class="text-red-500 text-sm">(noch nicht verifiziert)</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p>Es hat sich noch niemand als Spender:in eingetragen.</p>
    @endif

</div>

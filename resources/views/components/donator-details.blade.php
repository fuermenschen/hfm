<div>
    <x-page-title>Hallo {{ $donator->first_name }}</x-page-title>

    <p>Auf dieser Seite siehst du, bei wem du dich als Spender:in eingetragen hast.</p>

    <x-page-subtitle>Sportler:innen</x-page-subtitle>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if ($donations->count() > 0)
            <ul>
                @foreach ($donations as $donation)
                    <li class="list-disc">
                        {{ $donation->athlete->privacy_name }} ({{ $donation->athlete->public_id_string }})
                    </li>
                @endforeach
            </ul>
        @else
            <p>Es hat sich noch niemand als Spender:in eingetragen.</p>
        @endif
    </div>

</div>

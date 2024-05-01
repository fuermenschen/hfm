<div>
    <x-page-title>Hallo {{ $athlete->first_name }}</x-page-title>

    <p>Auf dieser Seite siehst du, wer sich bei dir als Spender:in eingetragen hat.</p>

    <x-page-subtitle>Spender:innen</x-page-subtitle>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if ($donations->count() > 0)
            @foreach ($donations as $donation)
                <span>{{ $donation->first_name }}</span>
            @endforeach
        @else
            <p>Es hat sich noch niemand als Spender:in eingetragen.</p>
        @endif
    </div>

</div>

@props(['variant' => 'medium', 'logo', 'title', 'description', 'url'])

@php
    use Illuminate\Support\Facades\Vite;
    use Illuminate\Support\Str;

    $modalId = 'sponsor-modal-' . Str::slug($title);
    $sizeClasses = [
    'small' => 'w-1/2 sm:w-1/4',
    'medium' => 'w-2/3 sm:w-1/3',
    'large' => 'w-4/5 sm:w-2/5',
][$variant] ?? 'w-40 sm:w-48 md:w-56 lg:w-64';
@endphp

<div class="{{ $sizeClasses }}">
    <div
        class="p-6 bg-white/50 rounded-lg shadow-lg cursor-pointer aspect-[2/1] flex items-center justify-center mx-auto"
        x-data="{}"
        x-on:click="$flux.modal('{{ $modalId }}').show()">
        <img src="{{ Vite::asset('resources/images/sponsor_logos/' . $logo . '.svg') }}"
             alt="{{ $title }} Logo"
             class="w-full h-auto object-contain mx-auto">


    </div>
    <flux:modal name="{{ $modalId }}" class="w-full space-y-6">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $title }}</flux:heading>
            </div>

            <flux:text>
                <p>{{ $description }}</p>
            </flux:text>

            <div class="flex justify-between items-center">
                <flux:button as="a" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                    Zur Website
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>



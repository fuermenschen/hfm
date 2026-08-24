@props([
    'route',
    'current' => false,
    'icon',
    'label' => '',
    'target' => null,
])

<li>
    @if ($route === 'admin.logout')
        <form
            method="POST"
            action="{{ route($route) }}"
            @class([
                'group flex gap-x-3 rounded-md text-sm font-semibold leading-6',
                'text-base-300 hover:text-base-50' => ! $current,
                'bg-accent text-accent-foreground' => $current,
            ])
        >
            @csrf
            <button type="submit" class="flex cursor-pointer gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold">
                <flux:icon :name="$icon" class="size-6 shrink-0" />
                {{ $label }}
            </button>
        </form>

    @else
        @php
            $href = \Illuminate\Support\Facades\Route::has($route) ? route($route) : url($route);
            $newTab = $target === '_blank';
        @endphp
        <a
            href="{{ $href }}"
            @if (! $newTab) wire:navigate.hover @endif
            @if ($target) target="{{ $target }}" @endif
            @if ($newTab) rel="noopener noreferrer" @endif
            @class([
                'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                'text-base-300 hover:text-base-50' => ! $current,
                'bg-accent text-accent-foreground' => $current,
            ])
        >
            <flux:icon :name="$icon" class="size-6 shrink-0" />
            {{ $label }}
        </a>
    @endif
</li>

@props([
    'route',
    'current' => false,
    'svg' => '',
    'label' => '',
    'target' => null,
])

<li>
    @if($route === 'logout')
        <form method="POST" action="{{ route($route) }}"
            @class([
              "group flex gap-x-3 rounded-md text-sm font-semibold leading-6",
              "text-[var(--color-base-300)] hover:text-[var(--color-base-50)]" => !$current,
              "bg-accent text-accent-foreground" => $current,
            ])>
            @csrf
            <button type="submit" class="flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6">
                <span class="h6 w-6 shrink-0">
                    {!! html_entity_decode($svg) !!}
                </span>
                {{$label}}
            </button>
        </form>

    @else
        @php
            $href = \Illuminate\Support\Facades\Route::has($route) ? route($route) : url($route);
            $newTab = $target === '_blank';
        @endphp
        <a href="{{ $href }}" @if(!$newTab) wire:navigate.hover @endif @if($target) target="{{ $target }}" @endif @if($newTab) rel="noopener noreferrer" @endif
            @class([
              "group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6",
              "text-[var(--color-base-300)] hover:text-[var(--color-base-50)]" => !$current,
              "bg-accent text-accent-foreground" => $current,
            ])>
        <span class="h6 w-6 shrink-0">
         {!! html_entity_decode($svg) !!}
        </span>
            {{$label}}
        </a>
    @endif
</li>

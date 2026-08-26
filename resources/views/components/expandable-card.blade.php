@props([
    'maxHeight' => 320,
    'fadeRatio' => 0.75,
    'contentId' => null,
    'expandLabel' => 'Mehr anzeigen',
    'expandMode' => 'text',
    'expandIcon' => 'chevron-down',
])

@php
    $contentId = $contentId ?? 'expandable-card-'.\Illuminate\Support\Str::random(8);
@endphp

<div
    x-data="{ expanded: false, hasOverflow: false }"
    x-init="$nextTick(() => (hasOverflow = $refs.content.scrollHeight > $refs.content.clientHeight))"
>
    <flux:card {{ $attributes->merge(['class' => 'relative overflow-hidden']) }}>
        <div
            id="{{ $contentId }}"
            x-ref="content"
            x-bind:style="expanded ? 'max-height: ' + $refs.content.scrollHeight + 'px' : 'max-height: {{ $maxHeight }}px'"
            class="overflow-hidden motion-safe:transition-[max-height] motion-safe:duration-300 motion-reduce:transition-none"
        >
            {{ $slot }}
        </div>

        <div
            x-cloak
            x-show="! expanded && hasOverflow"
            style="height: {{ $maxHeight * $fadeRatio }}px"
            class="pointer-events-none absolute inset-x-0 bottom-0 z-0 bg-gradient-to-t from-white to-transparent dark:from-zinc-800"
        ></div>

        <div
            x-cloak
            x-show="! expanded && hasOverflow"
            class="absolute inset-x-0 bottom-0 z-10 flex justify-center pb-4"
        >
            <flux:button
                type="button"
                variant="ghost"
                size="sm"
                x-on:click="expanded = true"
                x-bind:aria-expanded="expanded"
                aria-label="{{ $expandLabel }}"
                aria-controls="{{ $contentId }}"
            >
                @if ($expandMode === 'text')
                    {{ $expandLabel }}
                @else
                    <flux:icon
                        :name="$expandIcon"
                        @class([
                            'size-5',
                            'motion-safe:animate-bounce' => $expandMode === 'icon-animated',
                        ])
                        aria-hidden="true"
                    />
                @endif
            </flux:button>
        </div>
    </flux:card>
</div>

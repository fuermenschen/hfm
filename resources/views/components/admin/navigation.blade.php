@props([
    /** @var \array[] */
    'mainNavigation',
    /** @var \array[] */
    'secondaryNavigation'
])

<nav {{ $attributes->class(['flex flex-1 flex-col gap-y-7']) }}>
    <ul role="list" class="-mx-2 space-y-1">
        @foreach($mainNavigation as $nav)
            <x-admin.menu-item
                :route="$nav['route']"
                :current="$nav['current']"
                :icon="$nav['icon']"
                :label="$nav['label']"
                :target="$nav['target'] ?? null"
            />
        @endforeach
    </ul>
    <ul class="mt-auto">
        @foreach($secondaryNavigation as $nav)
            <x-admin.menu-item
                :route="$nav['route']"
                :current="$nav['current']"
                :icon="$nav['icon']"
                :label="$nav['label']"
                :target="$nav['target'] ?? null"
            />
        @endforeach
    </ul>
</nav>

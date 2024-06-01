@props([
    'question'
])

<div x-data="{ open: false }" class="pt-sm">
    <dt>
        <!-- Expand/collapse question button -->
        <button type="button" class="flex w-full items-start justify-between">
                        <span
                            class="text-base font-semibold leading-7 text-left" @click="open = !open">
                            {{ $question }}
                        </span>
            <span class="ml-6 flex h-7 items-center text-left">
                <!--
                  Icon when question is collapsed.

                  Item expanded: "hidden", Item collapsed: ""
                -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = true" x-show="!open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                </svg>
                <!--
                  Icon when question is expanded.

                  Item expanded: "", Item collapsed: "hidden"
                -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = false" x-show="open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                </svg>
              </span>
        </button>
    </dt>
    <dd class="mt-2 pr-12" x-show="open" x-transition>
        <p class="leading-7 text-sm flex flex-col space-y-xs">
            {{ $slot }}
        </p>
    </dd>
</div>

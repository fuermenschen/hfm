<header class="h-[5rem] sm:h-[5rem]" x-data="{ open: false }">
    <nav class="mx-auto flex items-baseline justify-between p-md" aria-label="Global">
        <a href="{{ route("home") }}" wire:navigate>
            <span class="sr-only">Höhenmeter für Menschen</span>
            <x-logo class="h-10 -mb-1.5 ml-0.5" />
        </a>
        <div class=" flex lg:hidden items">
            <button
                x-show="!open"
                @click="open = true"
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2.5"
            >
                <span class="sr-only">Menü öffnen</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
        <div class="hidden lg:flex lg:gap-x-9">
            @foreach($menuItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:key="{{ $item['name'] }}"
                    wire:navigate.hover
                    @class([
                        "text-sm leading-6 grow hover:text-hfm-light",
                        "text-hfm-dark dark:text-hfm-white font-normal" => !$item['active'],
                        "text-hfm-red dark:text-hfm-lightred font-medium" => $item['active'],
                    ])
                >
                    {{ $item['name'] }}
                </a>
            @endforeach
            @auth
                <a
                    href="{{ route("admin.dashboard") }}"
                    wire:navigate
                    class="text-sm leading-6 grow hover:text-hfm-light text-hfm-dark dark:text-hfm-white font-normal"
                >
                    Dashboard
                </a>
            @endauth
        </div>
        <div class="hidden lg:flex items">
            @guest
                <a href="{{ route("login") }}" wire:navigate
                   class="text-sm leading-6 grow hover:text-hfm-light text-hfm-dark dark:text-hfm-white font-normal flex flex-row space-x-2">
                    <span>Login</span>
                </a>
            @endguest
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="text-sm leading-6 grow hover:text-hfm-light text-hfm-dark dark:text-hfm-white font-normal flex flex-row space-x-2"
                    >
                        Logout
                    </button>
                </form>
            @endauth
        </div>
    </nav>
    <!-- Mobile menu, show/hide based on menu open state. -->
    <div
        x-show="open"
        wire:transition.opacity.duration.300ms
        class="fixed inset-0 z-10"
        role="dialog"
        aria-modal="true">
        <!-- Background backdrop, show/hide based on slide-over state. -->
        <div class="fixed inset-0 z-10"></div>
        <div
            class="fixed inset-y-0 right-0 z-10 w-full overflow-y-auto bg-hfm-white dark:bg-hfm-dark p-md sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
            <div class="flex items-center justify-between">
                <a wire:navigate href="/" class="-m-1.5 p-1.5">
                    <span class="sr-only">Höhenmeter für Menschen</span>
                    <x-logo class="h-10"
                            alt="" />
                </a>
                <button
                    @click="open = false"
                    type="button"
                    class="m-2.5 rounded-md text-hfm-dark dark:text-hfm-light"
                > <!-- TODO: check positions of buttons -->
                    <span class="sr-only">Menü schliessen</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-6 flow-root">
                <div class="-my-6">
                    <div class="space-y-2 py-6">
                        @foreach($menuItems as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                wire:key="{{ $item['name'] }}"
                                wire:navigate.hover
                                @class([
                                    "-mx-3 block rounded-lg px-3 py-2 text-base font-medium leading-7 hover:text-hfm-light",
                                    "text-hfm-red dark:text-hfm-lightred" => $item['active'],
                                    "text-hfm-dark dark:text-hfm-white" => !$item['active'],
                                ])
                            >
                                {{ $item['name'] }}
                            </a>
                        @endforeach
                        @auth
                            <a
                                href="{{ route("admin.dashboard") }}"
                                wire:navigate
                                class="-mx-3 block rounded-lg px-3 py-2 text-base font-medium leading-7 hover:text-hfm-light text-hfm-dark dark:text-hfm-white"
                            >
                                Dashboard
                            </a>
                        @endauth
                    </div>
                    @guest
                        <a
                            href="{{ route("login") }}"
                            wire:navigate
                            class="-mx-3 rounded-lg px-3 mt-8 py-2 text-base font-medium leading-7 hover:text-hfm-light text-hfm-dark dark:text-hfm-white flex flex-row"
                        >
                            <span>Login</span>

                        </a>
                    @endguest
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="-mx-3 rounded-lg px-3 mt-8 py-2 text-base font-medium leading-7 hover:text-hfm-light text-hfm-dark dark
                                :text-hfm-white flex flex-row"
                            >Logout
                            </button>
                        </form>
                    @endauth

                </div>
            </div>
        </div>
    </div>
</header>

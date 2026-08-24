@php use Illuminate\Support\Facades\Vite; @endphp

@props([
    // Base filename (without extension) for the landing image, e.g. "01", "07"
    'img' => '02',
])

@php
    // Normalize provided image value by stripping any extension and leading zeros
    // Examples: "07.png" -> "7", "01" -> "1", "5" -> "5"
    $imgBase = preg_replace('/\.(png|webp|avif)$/i', '', (string) $img);
    $imgNum = ltrim($imgBase, '0');
    if ($imgNum === '') {
        $imgNum = '2';
    }
    if (! preg_match('/^\d+$/', $imgNum)) {
        $imgNum = '2';
    }
    $n = (int) $imgNum;
    if ($n < 2 || $n > 14) {
        $imgNum = '2';
    }
@endphp

@push('head')
    <link
        rel="preload"
        as="image"
        imagesrcset="{{ Vite::asset("resources/images/landing_page/{$imgNum}.png") }}"
        imagesizes="100vw"
    />
@endpush

<div class="hfm-hero full-bleed relative isolate flex h-[calc(100dvh-var(--nav-h)-var(--content-pt,0px)-var(--content-pb,0px)+var(--hero-reserve,8px))] flex-col overflow-hidden">
    <div class="hfm-hero__visual absolute inset-0 -z-10 h-full w-full portrait:relative portrait:inset-auto portrait:z-0 portrait:h-auto portrait:min-h-[24vh] portrait:w-full portrait:flex-1">
        <picture>
            <!-- Low quality blurred placeholder shown immediately -->
            <img
                class="hfm-hero__img hfm-hero__img--placeholder block h-full w-full object-cover object-[center_55%] portrait:h-full portrait:max-h-none"
                src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='18' viewBox='0 0 32 18'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' x2='1' y1='0' y2='1'%3E%3Cstop offset='0%25' stop-color='%23d9e3f0'/%3E%3Cstop offset='100%25' stop-color='%23b7c3d4'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='18' fill='url(%23g)'/%3E%3C/svg%3E"
                width="32"
                height="18"
                decoding="async"
                alt=""
                aria-hidden="true"
                role="presentation"
            />
            <!-- Full resolution image will be swapped in by JS when loaded -->
            <img
                class="hfm-hero__img hfm-hero__img--full block h-full w-full object-cover object-[center_55%] portrait:h-full portrait:max-h-none"
                data-src="{{ Vite::asset("resources/images/landing_page/{$imgNum}.png") }}"
                width="1536"
                height="1024"
                sizes="100vw"
                decoding="async"
                fetchpriority="high"
                alt=""
                aria-hidden="true"
                role="presentation"
            />
            <noscript>
                <img
                    class="hfm-hero__img block h-full w-full object-cover object-[center_55%] portrait:h-full portrait:max-h-none"
                    src="{{ Vite::asset("resources/images/landing_page/{$imgNum}.png") }}"
                    width="1536"
                    height="1024"
                    sizes="100vw"
                    alt=""
                />
            </noscript>
        </picture>
        <!-- Global scrim to ensure text contrast on any image -->
        <div class="hfm-hero__overlay pointer-events-none absolute inset-0 z-10 mix-blend-normal"></div>
    </div>

    <div
        class="hfm-hero__badge absolute left-2 z-50 hidden items-center justify-center sm:left-6 md:flex portrait:hidden"
        aria-hidden="true"
    >
        <div class="hfm-hero__badgeCircle">
            <img
                src="{{ Vite::asset('resources/images/hero_badge.svg') }}"
                alt=""
                width="170"
                height="170"
                class="block h-full w-full"
                decoding="async"
            />
        </div>
    </div>

    <div class="hfm-hero__content text-hfm-dark dark:text-hfm-white absolute right-0 bottom-0 left-0 z-20 px-6 text-inherit portrait:static portrait:mt-4">
        <div class="hfm-hero__inner mx-auto w-full max-w-[min(88vw,70ch)] pt-6 pb-6 text-center sm:pb-10">
            @if (isset($kicker))
                <p class="hfm-hero__kicker text-[clamp(0.9rem,1.8vw,1.125rem)] font-medium">{{ $kicker }}</p>
            @endif

            @if (isset($title))
                <div class="relative mt-3 sm:mt-5">
                    <div
                        aria-hidden="true"
                        class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center"
                    >
                        <div class="hfm-hero__titleRadial"></div>
                    </div>
                    <h1 class="hfm-hero__title text-[clamp(2rem,6vw,3.5rem)] leading-tight font-extrabold tracking-tight text-balance wrap-break-word">
                        {{ $title }}
                    </h1>
                </div>
            @endif

            @if (isset($copy))
                <p class="hfm-hero__copy mt-4 text-[clamp(0.95rem,1.9vw,1.125rem)] leading-7 sm:mt-6 sm:leading-8">
                    {{ $copy }}
                </p>
            @endif

            @if (isset($ctas))
                <div class="hfm-hero__ctas mt-4 flex items-center justify-center gap-x-6 sm:mt-8">{{ $ctas }}</div>
            @endif

            @if (isset($partners))
                <div class="hfm-hero__partners mx-auto mt-6 w-full max-w-[min(88vw,70ch)] sm:mt-8">{{ $partners }}</div>
            @endif
        </div>
    </div>
</div>

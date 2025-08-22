@php use Illuminate\Support\Facades\Vite; @endphp

@props([
    // Base filename (without extension) for the landing image, e.g. "01", "07"
    'img' => '01',
])

@php
    // Normalize provided image value by stripping any extension and leading zeros
    // Examples: "07.png" -> "7", "01" -> "1", "5" -> "5"
    $imgBase = preg_replace('/\.(png|webp|avif)$/i', '', (string) $img);
    $imgNum = ltrim($imgBase, '0');
    if ($imgNum === '') { $imgNum = '1'; }
    if (!preg_match('/^\d+$/', $imgNum)) { $imgNum = '1'; }
    $n = (int) $imgNum;
    if ($n < 1 || $n > 14) { $imgNum = '1'; }
@endphp

@push('head')
<link rel="preload" as="image" imagesrcset="{{ Vite::asset("resources/images/landing_page/{$imgNum}.png") }}" imagesizes="100vw">
@endpush

<div class="hfm-hero relative isolate full-bleed h-[calc(100dvh-var(--nav-h)-var(--content-pt,0px)-var(--content-pb,0px)+var(--hero-reserve,8px))] flex flex-col overflow-hidden"
>
    <div class="hfm-hero__visual -z-10 absolute inset-0 h-full w-full portrait:relative portrait:inset-auto portrait:flex-1 portrait:h-auto portrait:min-h-[24vh] portrait:w-full portrait:z-0">
        <picture>
            <img
                class="hfm-hero__img block w-full h-full object-cover object-[center_55%] portrait:h-full portrait:max-h-none"
                src="{{ Vite::asset("resources/images/landing_page/{$imgNum}.png") }}"
                width="1920" height="1080"
                sizes="100vw"
                decoding="async" fetchpriority="high" alt="" aria-hidden="true" role="presentation"
            />
        </picture>
        <!-- Global scrim to ensure text contrast on any image -->
        <div class="hfm-hero__overlay pointer-events-none absolute inset-0 z-10 mix-blend-normal"></div>
    </div>

    <div class="hidden md:flex portrait:hidden absolute left-2 sm:left-6 items-center justify-center z-50 hfm-hero__badge" aria-hidden="true">
        <div class="hfm-hero__badgeCircle">
            <img src="{{ Vite::asset('resources/images/hero_badge.svg') }}" alt="" width="170" height="170" class="block w-full h-full" decoding="async" />
        </div>
    </div>

    <div class="hfm-hero__content absolute left-0 right-0 bottom-0 z-20 text-inherit px-6 text-hfm-dark dark:text-hfm-white portrait:static portrait:mt-4">
        <div class="hfm-hero__inner w-full max-w-[min(88vw,70ch)] mx-auto text-center pt-6 pb-6 sm:pb-10">
            @if (isset($kicker))
                <p class="hfm-hero__kicker text-[clamp(0.9rem,1.8vw,1.125rem)] font-medium">{{ $kicker }}</p>
            @endif

            @if (isset($title))
                <div class="relative mt-3 sm:mt-5">
                    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center">
                        <div class="hfm-hero__titleRadial"></div>
                    </div>
                    <h1 class="hfm-hero__title text-[clamp(2rem,6vw,3.5rem)] font-extrabold leading-tight tracking-tight">{{ $title }}</h1>
                </div>
            @endif

            @if (isset($copy))
                <p class="hfm-hero__copy mt-4 sm:mt-6 text-[clamp(0.95rem,1.9vw,1.125rem)] leading-7 sm:leading-8">{{ $copy }}</p>
            @endif

            @if (isset($ctas))
                <div class="hfm-hero__ctas mt-4 sm:mt-8 flex items-center justify-center gap-x-6">
                    {{ $ctas }}
                </div>
            @endif

            @if (isset($partners))
                <div class="hfm-hero__partners mt-6 sm:mt-8 mx-auto w-full max-w-[min(88vw,70ch)]">
                    {{ $partners }}
                </div>
            @endif
        </div>
    </div>
</div>

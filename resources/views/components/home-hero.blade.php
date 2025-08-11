@php use Illuminate\Support\Facades\Vite;
    // Pick a random landing page image (1..14)
    $landingImg = rand(1, 14) . '.png';
@endphp

<style>
    /* Scoped styles for the hero */
        .hfm-hero { --nav-h: 5rem; --slope: clamp(8px, 2.4vw, 40px); }
    .hfm-hero, .hfm-hero * { box-sizing: border-box; }
    .hfm-hero__visual {
        position: absolute; inset: 0; height: 100%; width: 100%;
        clip-path: polygon(0 var(--slope), 100% 0, 100% 100%, 0 100%);
    }
        .hfm-hero__img { width: 100%; height: 100%; object-fit: cover; object-position: center 55%; display:block; }
            .hfm-hero__overlay { position:absolute; inset:0; pointer-events:none; z-index:1; display:block;
            /* Gradient only on the lower part for contrast; light vs dark via media query */
            background: linear-gradient(
                to top,
                rgba(248, 250, 252, 0.94) 0%,      /* hfm.white */
                rgba(248, 250, 252, 0.86) 20%,
                rgba(248, 250, 252, 0.72) 34%,
                rgba(248, 250, 252, 0.00) 58%
            );
            clip-path: polygon(0 var(--slope), 100% 0, 100% 100%, 0 100%);
        }
        @media (prefers-color-scheme: dark) {
            .hfm-hero__overlay {
                background: linear-gradient(
                    to top,
                    rgba(27, 46, 71, 0.88) 0%,     /* hfm.dark */
                    rgba(27, 46, 71, 0.70) 22%,
                    rgba(27, 46, 71, 0.52) 38%,
                    rgba(27, 46, 71, 0.00) 58%
                );
            }
        }
    .hfm-hero__content {
                position: absolute; left:0; right:0; bottom:0; z-index: 2; color: inherit;
    }
    /* Badge: only show when there's enough height; shrink on short viewports */
    .hfm-hero__badge { top: 5rem; }
    .hfm-hero__badgeCircle { width: clamp(7rem, 14vh, 12rem); height: clamp(7rem, 14vh, 12rem); transform: rotate(-8deg); transform-origin: center; }
    @media (max-height: 680px) {
        .hfm-hero__badge { display: none !important; }
    }
    /* Portrait or square: stack image then content; limit image height to avoid dominance */
    @media (max-aspect-ratio: 1/1) {
        .hfm-hero__visual { position: relative; height: min(55vh, 90vw); }
                .hfm-hero__content { position: relative; bottom: auto; color: inherit; }
                .hfm-hero__overlay { display: none; }
    }
    /* Very wide: ensure image reaches the bottom (it's already height:100%) */
        @media (min-aspect-ratio: 5/3) {
            .hfm-hero__img { object-position: center 50%; }
    }
</style>

<div
    id="hfm-hero"
    class="hfm-hero relative isolate w-screen min-h-[calc(100svh-5rem)] overflow-hidden flex flex-col"
        style="margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw);">
        <!-- Image + clipped gradient (no gray above the slope) -->
    <div class="hfm-hero__visual -z-10">
                <img class="hfm-hero__img"
                         src="{{ Vite::asset('resources/images/landing_page/' . $landingImg) }}"
                         alt="Decorative image related to sports for Höhenmeter für Menschen" />
    <div class="hfm-hero__overlay"></div>
        </div>

    <!-- Optional red circle badge (shown on md+, hidden on short screens) -->
    <div class="hidden md:flex absolute left-2 sm:left-6 items-center justify-center z-10 hfm-hero__badge">
        <div class="rounded-full bg-hfm-red text-white p-4 flex items-center justify-center text-center shadow-lg shadow-hfm-dark/20 hfm-hero__badgeCircle">
            <p class="text-xs sm:text-sm leading-snug font-semibold">Jetzt mitmachen und mit jeder Runde Gutes tun!</p>
        </div>
    </div>

    <!-- Content on gradient (bottom for wide; stacked under image for portrait) -->
    <div class="hfm-hero__content px-6 text-hfm-dark dark:text-hfm-white">
        <div class="w-full max-w-[720px] mx-auto text-center pt-6 pb-6 sm:pb-10">
            <p class="text-sm sm:text-base">Samstag, 13. September 2025 in Winterthur</p>
            <h1 class="mt-3 sm:mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight">Höhenmeter für&nbsp;Menschen</h1>
            <p class="mt-4 sm:mt-6 text-sm sm:text-lg leading-8">Ein Spendenlauf für Winterthur. Wir rennen, fahren, rollen – für lokale Benefizpartner:innen. Bist auch du am Start?</p>
            <div class="mt-4 sm:mt-8 flex items-center justify-center gap-x-6">
                <a href="#info"
                   class="rounded-md bg-hfm-red px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-hfm-dark dark:hover:bg-hfm-light">Mehr dazu</a>
                <a href="{{ route('become-donator') }}" wire:navigate.hover
                   class="text-xs sm:text-sm font-semibold leading-6">Spender:in werden <span aria-hidden="true">→</span></a>
            </div>

            <!-- Partners live inside content so they don't jump to the top on wide screens -->
            <div class="mt-6 sm:mt-8 mx-auto w-full max-w-[720px]">
                <div class="grid grid-cols-3 gap-x-4 sm:gap-x-8 gap-y-4 max-h-[25vh] w-full mx-auto overflow-clip">
                    <h3 class="col-span-3 text-xs sm:text-sm opacity-90">Unsere Benefizpartner:innen</h3>

                    <x-home-hero-partner assetUrl="resources/images/bruehlgut_light.svg"
                                         assetUrlDark="resources/images/bruehlgut_dark.svg"
                                         imgAlt="Logo Brühlgut Stiftung"
                                         beneficiaryName="Brühlgut Stiftung"
                                         beneficiaryUrl="https://www.xn--brhlgut-o2a.ch/" />

                    <x-home-hero-partner assetUrl="resources/images/iks_light.svg"
                                         assetUrlDark="resources/images/iks_dark.svg"
                                         imgAlt="Logo Institut Kinderseele Schweiz"
                                         beneficiaryName="Institut Kinderseele Schweiz"
                                         beneficiaryUrl="https://kinderseele.ch" />

                    <x-home-hero-partner assetUrl="resources/images/143_light.svg"
                                         assetUrlDark="resources/images/143_dark.svg"
                                         imgAlt="Logo Tel. 143 &ndash; Die Dargebotene Hand"
                                         beneficiaryName="Tel. 143 &ndash; Die Dargebotene Hand"
                                         beneficiaryUrl="https://143.ch" />
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
    (function() {
        const hero = document.getElementById('hfm-hero');
        if (!hero) return;
        const setHeroMinHeight = () => {
            // Distance from top of viewport to hero top
            const rect = hero.getBoundingClientRect();
            const top = rect.top; // relative to current scroll position
            const vh = window.innerHeight || document.documentElement.clientHeight;
            const minH = Math.max(320, Math.round(vh - top));
            hero.style.minHeight = minH + 'px';
        };
        const onResize = () => requestAnimationFrame(setHeroMinHeight);
        window.addEventListener('load', setHeroMinHeight, { once: true });
        window.addEventListener('resize', onResize);
        // In case of layout flashes after fonts/nav load, adjust shortly after
        setTimeout(setHeroMinHeight, 50);
        setTimeout(setHeroMinHeight, 300);
    })();
</script>












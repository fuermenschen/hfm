@php use Illuminate\Support\Facades\Vite;
 $randomImgPortrait = sprintf('%02d', rand(1, 8));
 $randomImgLandscape = sprintf('%02d', rand(1, 7));

@endphp
@props(['athleteCount', 'donationCount'])
<div class="relative">
    <div class="mx-auto max-w-7xl lg:flex lg:justify-between lg:px-8 xl:justify-end">
        <div
            id="info"
            class="mt-6 sm:mt-8 lg:mt-10 lg:flex lg:w-1/2 lg:shrink lg:grow-0 xl:absolute xl:inset-y-0 xl:right-1/2 xl:w-1/2 -mx-9 lg:mr-9">
            <div class="hidden lg:block relative h-80 lg:-ml-48 lg:h-auto lg:w-full lg:grow">
                <img class="absolute inset-0 h-full w-full bg-gray-50 object-cover"
                     src="{{ Vite::asset('resources/images/sport_portrait_' . $randomImgPortrait . '.jpeg') }}"
                     alt="decorative image of one or more people doing sport">
            </div>
            <div class="block lg:hidden relative h-80">
                <img class="absolute inset-0 h-full w-full bg-gray-50 object-cover"
                     src="{{ Vite::asset('resources/images/sport_landscape_' . $randomImgLandscape . '.jpeg') }}"
                     alt="decorative image of one or more people doing sport">
            </div>
        </div>
        <div class="px-6 lg:contents">
            <div
                class="mx-auto max-w-2xl pb-24 pt-16 sm:pb-32 sm:pt-20 lg:ml-8 lg:mr-0 lg:w-full lg:max-w-lg lg:flex-none lg:pt-32 xl:w-1/2">
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ $currentDonationEvent?->contentValue('home.about_heading', '') }}
                </h1>
                <div class="mt-6 max-w-none text-xl leading-8 prose prose-p:my-0 dark:prose-invert">
                    {!! $currentDonationEvent?->contentMarkdown('home.about_intro_md') !!}
                </div>
                <div class="mt-10 max-w-xl text-base leading-7 lg:max-w-none">
                    <div class="max-w-none prose prose-p:my-0 dark:prose-invert">
                        {!! $currentDonationEvent?->contentMarkdown('home.about_body_md') !!}
                    </div>
                    <ul role="list" class="mt-8 space-y-8">
                        <li class="flex gap-x-3 items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 class="stroke-hfm-red dark:stroke-hfm-lightred w-6 mt-3 flex-none">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>


                            <span class="flex-grow">
                                <strong class="font-semibold"> Werde Sportler:in </strong> Egal, ob Couch-Potato oder Marathonläufer:in, ob du mit dem Velo oder dem Rollstuhl kommst: Dein Einsatz bewegt! Bist auch du dabei als Sportler:in?
                                <x-inline-link
                                    href=" {{ route('become-athlete') }}">Melde dich als Sportler:in!</x-inline-link>
                            </span>
                        </li>
                        <li class="flex gap-x-3 items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 class="stroke-hfm-red dark:stroke-hfm-lightred w-6 mt-3 flex-none">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>


                            <span class="flex-grow">
                                <strong class="font-semibold"> Werde Spender:in </strong> Du lässt lieber andere schwitzen? Unterstütze die Sportler:innen dabei, Spenden für die Benefizpartner:innen zu finden. Egal ob 10 oder 1000 Franken: Dein Einsatz bewegt! Bist auch du dabei als Spender:in? <x-inline-link
                                    href="{{ route('become-donor') }}">Melde dich als Spender:in!</x-inline-link>
                            </span>
                        </li>
                    </ul>
                    <p class="mt-8"><strong class="font-semibold"> Sämtliche Spenden gehen zu 100% an die
                            Benefizpartner:innen. </strong> Die ganze Organisation und die damit verbundenen Kosten
                        übernimmt der
                        <x-inline-link href="{{ route('association') }}">Verein für Menschen.</x-inline-link>
                    </p>

                    <h2 class="mt-16 text-2xl font-bold tracking-tight">Wer profitiert?</h2>
                    <p class="mt-6">Wenn du als Sportler:in mitmachst, kannst du wählen, welche:r der Benefizpartner:innen
                        von deinem Einsatz profitiert.</p>
                    <ul role="list" class="mt-8 space-y-8">
                        @forelse ($currentEventPartners as $partner)
                            <li class="flex gap-x-3">
                                <span>
                                    <strong class="font-semibold"> {{ $partner->name }} </strong>
                                    @if (filled($partner->beneficiary_blurb))
                                        {{ $partner->beneficiary_blurb }}
                                    @endif
                                    @if (filled($partner->url))
                                        <x-inline-link href="{{ $partner->url }}" target="_blank">{{ $partner->name }}</x-inline-link>
                                    @endif
                                </span>
                            </li>
                        @empty
                            <li class="flex gap-x-3">
                                <span class="text-sm text-slate-600 dark:text-slate-300">Aktuell sind keine Benefizpartner:innen für diesen Anlass publiziert.</span>
                            </li>
                        @endforelse
                    </ul>

                </div>
            </div>
        </div>
    </div>
</div>

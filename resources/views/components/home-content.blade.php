@php use Illuminate\Support\Facades\Vite; @endphp
@props(['athleteCount', 'donationCount'])
<div class="relative">
    <div class="mx-auto max-w-7xl lg:flex lg:justify-between lg:px-8 xl:justify-end">
        <div
            id="info"
            class="lg:flex lg:w-1/2 lg:shrink lg:grow-0 xl:absolute xl:inset-y-0 xl:right-1/2 xl:w-1/2 -mx-md lg:mr-md">
            <div class="hidden lg:block relative h-80 lg:-ml-8 lg:h-auto lg:w-full lg:grow xl:ml-0">
                <img class="absolute inset-0 h-full w-full bg-gray-50 object-cover"
                     src=" {{ Vite::asset("resources/images/running_1.jpg") }}"
                     alt="Image of a person running">
            </div>
            <div class="block lg:hidden relative h-80 lg:-ml-8 lg:h-auto lg:w-full lg:grow xl:ml-0">
                <img class="absolute inset-0 h-full w-full bg-gray-50 object-cover"
                     src=" {{ Vite::asset("resources/images/running_2.jpg") }}"
                     alt="Image of a person running">
            </div>
        </div>
        <div class="px-6 lg:contents">
            <div
                class="mx-auto max-w-2xl pb-24 pt-16 sm:pb-32 sm:pt-20 lg:ml-8 lg:mr-0 lg:w-full lg:max-w-lg lg:flex-none lg:pt-32 xl:w-1/2">
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Um was geht
                    es?</h1>
                <p class="mt-6 text-xl leading-8">Höhenmeter für Menschen ist ein Spendenlauf in
                    Winterthur. Es werden Spenden für lokale Benefizpartner:innen gesammelt.</p>
                <div class="mt-10 max-w-xl text-base leading-7 lg:max-w-none">
                    <p>Der Spendenlauf ist ähnlich organisiert wie ein klassischer «Sponsorenlauf». Nur wird nicht für
                        einen Verein gesammelt, sondern für Kinder psychisch kranker Eltern, für Menschen mit
                        Behinderung und für Menschen in schwierigen Lebenssituationen.</p>
                    <ul role="list" class="mt-8 space-y-8">
                        <li class="flex gap-x-3 items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 class="stroke-hfm-red w-6 mt-xs flex-none">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>


                            <span class="flex-grow">
                                <strong class="font-semibold"> Werde Sportler:in </strong> Egal, ob Couch-Potato oder Marathonläufer:in, ob du mit dem Velo oder dem Rollstuhl kommst: Dein Einsatz bewegt! Bist auch du dabei als {{ $athleteCount+1 }}.&nbsp;Sportler:in? <a
                                    href="{{ route('become-athlete') }}" class="text-hfm-red hover:font-medium"
                                    wire:navigate.hover>Melde dich jetzt an!</a>
                            </span>
                        </li>
                        <li class="flex gap-x-3 items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 class="stroke-hfm-red w-6 mt-xs flex-none">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>


                            <span class="flex-grow">
                                <strong class="font-semibold"> Werde Spender:in </strong> Du lässt lieber andere schwitzen? Unterstütze die Sportler:innen dabei, Spenden für die Benefizpartner:innen zu finden. Egal ob 10 oder 1000 Franken: Dein Einsatz bewegt! Bist auch du dabei als {{ $donationCount+1 }}.&nbsp;Spender:in? <a
                                    href="{{ route('become-donator') }}" class="text-hfm-red hover:font-medium"
                                    wire:navigate.hover>Melde dich jetzt an!</a>
                            </span>
                        </li>
                    </ul>
                    <p class="mt-8"><strong class="font-semibold"> Sämtliche Spenden gehen zu 100% an die
                            Benefizpartner:innen. </strong> Die ganze Organisation und die damit verbundenen Kosten
                        übernimmt der <a href="https://rt25.ch" class="text-hfm-red hover:font-medium"
                                         target="_blank">Round Table 25 Winterthur</a>.</p>

                    <h2 class="mt-16 text-2xl font-bold tracking-tight">Wer profitiert?</h2>
                    <p class="mt-6">Wenn du als Sportner:in mitmachst, kannst du wählen, welche:r der drei
                        Benefizpartner:innen von deinem Einsatz profitiert.</p>
                    <ul role="list" class="mt-8 space-y-8">
                        <li class="flex gap-x-3">
                            <span>
                                <strong class="font-semibold"> Brühlgut Stiftung </strong> Die Brühlgut Stiftung
                                unterstützt Menschen mit Behinderung in ihrem Alltag und bietet ihnen ein Zuhause. <a
                                    href="https://www.bruehlgut.ch" class="text-hfm-red hover:font-medium"
                                    target="_blank">Mehr erfahren</a>.
                            </span>
                        </li>
                        <li class="flex gap-x-3">
                            <span>
                                <strong class="font-semibold"> Institut Kinderseele Schweiz </strong> Das Institut
                                Kinderseele Schweiz unterstützt Kinder psychisch kranker Eltern. <a
                                    href="https://www.kinderseele.ch" class="text-hfm-red hover:font-medium"
                                    target="_blank">Mehr erfahren</a>.
                            </span>
                        </li>
                        <li class="flex gap-x-3">
                            <span>
                                <strong class="font-semibold"> Dargebotene Hand Winterthur </strong> Die Dargebotene Hand
                                Winterthur bietet Menschen in schwierigen Lebenssituationen ein offenes Ohr. <a
                                    href="https://www.143.ch" class="text-hfm-red hover:font-medium"
                                    target="_blank">Mehr erfahren</a>.
                            </span>
                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
</div>


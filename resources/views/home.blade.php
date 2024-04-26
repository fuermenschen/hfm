<div>
    <x-logo-and-text class="mx-auto w-[600px] max-w-full p-10"/>
    Es haben sich schon {{ $athleteCount }} Sportler:innen registriert. Mache auch du mit bei der wohltätigen Aktion!

    <div class="sm:grid sm:grid-cols-2 max-w-full sm:gap-md mt-md">
        <x-button icon="information-circle" label="Erfahre mehr" href="/ueber-das-projekt" wire:navigate red xl/>
        <x-button icon="fire" label="Jetzt Sportler:in werden" href="/sportlerin-werden" wire:navigate
                  red xl/>
        <x-button icon="heart" label="Jetzt Spender:in werden" href="/spenderin-werden" wire:navigate
                  red xl/>
        <x-button icon="flag" label="Details zum Anlass" href="/informationen-zum-anlass" wire:navigate red xl/>
    </div>
</div>

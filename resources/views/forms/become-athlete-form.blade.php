<form wire:submit="save"
      class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    @csrf

    <flux:input wire:model.blur="first_name" label="Vorname" icon-trailing="user" placeholder="Francesca"
                autocomplete="given-name"
                required type="text" />

    <flux:input icon-trailing="user" label="Nachname" placeholder="Arslan" wire:model.blur="last_name" required
                type="text"
                autocomplete="family-name" />

    <flux:input icon-trailing="home" label="Adresse" placeholder="Zelglistrasse 41" wire:model.blur="address"
                required type="text" autocomplete="street-address"
    />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <flux:input icon-trailing="home" label="PLZ" placeholder="8406" mask="9999"
                            wire:model.blur="zip_code"
                            class="basis-1/3" required type="text" autocomplete="postal-code"
                />
            </span>
            <span class="grow">
                <flux:input icon-trailing="home" label="Ort" placeholder="Winterthur" wire:model.blur="city"
                            class="grow" required type="text" autocomplete="address-level2"
                />
            </span>
        </span>

    <flux:input icon-trailing="envelope" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
                wire:model.blur="email" required type="email" autocomplete="email"
    />

    <flux:input icon-trailing="envelope" label="E-Mail bestätigen " placeholder="francesca.arslan@posteo.ch"
                wire:model.blur="email_confirmation" required type="email" autocomplete="off"
    />

    <flux:input icon-trailing="phone" label="Telefon"
                mask="999 999 99 99"
                placeholder="079 123 45 67"
                wire:model.blur="phone_number" required type="tel" autocomplete="tel"
    />

    <flux:radio.group wire:model.blur="adult" label="Bist du volljährig?">
        <flux:radio value="false" label="Nein" checked autocomplete="off" />
        <flux:radio value="true" label="Ja" autocomplete="off" />
    </flux:radio.group>


    <flux:separator class="sm:col-span-2" />


    <flux:radio.group wire:model.blur="sport_type_id" label="Sportart" autocomplete="off">
        @if (!$sport_types || $sport_types->isEmpty())
            <flux:radio disabled value="0" label="Keine Sportarten verfügbar" />
        @else
            @foreach ($sport_types as $type)
                <flux:radio value="{{ $type->id }}" label="{{ $type->name }}" />
            @endforeach
        @endif
    </flux:radio.group>

    <span>
        <flux:input icon-trailing="fire" label="Geschätzte Anzahl Runden" placeholder="11"
                    wire:model.blur="rounds_estimated" required autocomplete="off" />
        <button type="button" wire:click="showNumRoundsInfo"
                class="text-xs underline mt-xs">Informationen zur Strecke</button>
    </span>
    <flux:separator class="sm:col-span-2" />
    <span>
        <flux:radio.group wire:model.blur="partner_id" label="Ich möchte sammeln für" autocomplete="off"
                          placeholder="Bitte auswählen">
            @if (!$partners || $partners->isEmpty())
                <flux:radio disabled value="0" label="Keine Partner:innen verfügbar" />
            @else
                @foreach ($partners as $partner)
                    <flux:radio value="{{ $partner->id }}" label="{{ $partner->name }}" />
                @endforeach
            @endif
        </flux:radio.group>
        <button type="button" wire:click="showDistributionInfo"
                class="text-xs underline mt-xs">Informationen zur Verteilung der Spenden</button>
    </span>
    <flux:separator class="sm:col-span-2" />
    <flux:textarea label="Kommentar" badge="optional"
                   placeholder="Ich freu mich druf. Bin zwar nöd mega sportlich, aber das isch ja egal. Hauptsach es chunnt e gueti Summe zäme!"
                   wire:model.blur="comment" autocomplete="off" />

    <span class="sm:col-span-2">
            <x-toggle wire:model.bool.live="privacy"
                      label="Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden." />
                <button type="button" wire:click="showPrivacyInfo"
                        class="text-xs underline mt-xs">Was heisst das?</button>
    </span>

    <x-honey />
    <flux:button
        icon="paper-airplane" label="Senden" type="submit" class="justify-self-start">
        Senden
    </flux:button>
</form>

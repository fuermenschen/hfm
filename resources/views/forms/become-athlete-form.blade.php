<form wire:submit="save"
      class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    @csrf

    <x-input right-icon="user" label="Vorname" placeholder="Francesca" wire:model.blur="first_name" required
             type="text" autocomplete="given-name"
    />

    <x-input right-icon="user" label="Nachname" placeholder="Arslan" wire:model.blur="last_name" required type="text"
             autocomplete="family-name" />

    <x-input right-icon="home" label="Adresse" placeholder="Zelglistrasse 41" wire:model.blur="address"
             required type="text" autocomplete="street-address"
    />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <x-inputs.maskable mask="####" right-icon="home" label="PLZ" placeholder="8406"
                                   wire:model.number.blur="zip_code"
                                   class="basis-1/3" required type="text" autocomplete="postal-code"
                />
            </span>
            <span class="grow">
                <x-input right-icon="home" label="Ort" placeholder="Winterthur" wire:model.blur="city"
                         class="grow" required required type="text" autocomplete="address-level2"
                />
            </span>
        </span>

    <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email" required type="email" autocomplete="email"
    />

    <x-input right-icon="mail" label="E-Mail bestätigen " placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email_confirmation" required type="email" autocomplete="off"
    />

    <x-inputs.phone right-icon="phone" label="Telefon"
                    mask="['### ### ## ##']" placeholder="079 123 45 67"
                    wire:model.blur="phone_number" required type="tel" autocomplete="tel"
    />

    <span class="space-y-1">
        <span class="text-gray-700 dark:text-gray-400 text-sm font-medium">Bist du volljährig?</span>
            <span class="flex flex-row space-x-4">
            <x-radio label="Nein" value="false" wire:model.boolean="adult" autocomplete="off" />
            <x-radio label="Ja" value="true" wire:model.boolean="adult" autocomplete="off" />
            </span>
        </span>

    <x-native-select label="Sportart" wire:model="sport_type_id" autocomplete="off">
        <option disabled value="0">Bitte auswählen</option>
        @if (!$sport_types || $sport_types->isEmpty())
            <option disabled value="0">Keine Sportarten verfügbar</option>
        @else
            @foreach ($sport_types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        @endif
    </x-native-select>

    <span>
        <x-inputs.number right-icon="fire" label="Geschätzte Anzahl Runden" placeholder="11"
                         wire:model.blur="rounds_estimated" required autocomplete="off" />
        <button type="button" wire:click="showNumRoundsInfo"
                class="text-xs underline mt-xs">Informationen zur Strecke</button>
    </span>

    <span>
        <x-native-select label="Ich möchte sammeln für" wire:model="partner_id" autocomplete="off">
            <option disabled value="0">Bitte auswählen</option>
            @if (!$partners || $partners->isEmpty())
                <option disabled value="0">Keine Partner:innen verfügbar</option>
            @else
                @foreach ($partners as $partner)
                    <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                @endforeach
            @endif
        </x-native-select>
        <button type="button" wire:click="showDistributionInfo"
                class="text-xs underline mt-xs">Informationen zur Verteilung der Spenden</button>
    </span>

    <x-textarea label="Kommentar"
                placeholder="Ich freu mich druf. Bin zwar nöd mega sportlich, aber das isch ja egal. Hauptsach es chunnt e gueti Summe zäme!"
                wire:model.live.debounce="comment" hint="{{ strlen($comment) }}/2000" autocomplete="off" />

    <span class="sm:col-span-2 flex flex-row space-x-sm items-center">
            <x-toggle wire:model.boolean="privacy" autocomplete="off" required />
            <span class="text-md">
                Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden.
                <button type="button" wire:click="showPrivacyInfo"
                        class="text-hfm-red dark:text-hfm-lightred underline">Was heisst das?</button>
            </span>
        </span>

    <x-honey />
    <x-button label="Senden" type="submit" spinner="save" class="justify-self-start" />
</form>

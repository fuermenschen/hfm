<form wire:submit="save"
      class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    @csrf

    <x-input right-icon="user" label="Vorname" placeholder="Francesca" wire:model.blur="first_name" required />

    <x-input right-icon="user" label="Nachname" placeholder="Arslan" wire:model.blur="last_name" required />

    <x-input right-icon="home" label="Adresse" placeholder="Zelglistrasse 41" wire:model.blur="address"
             hint="Wir werden dir ein Starter-Kit senden." required />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <x-inputs.maskable mask="####" right-icon="home" label="PLZ" placeholder="8406"
                                   wire:model.number.blur="zip_code"
                                   class="basis-1/3" required />
            </span>
            <span class="grow">
                <x-input right-icon="home" label="Ort" placeholder="Winterthur" wire:model.blur="city"
                         class="grow" required />
            </span>
        </span>

    <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email" required />

    <x-input right-icon="mail" label="E-Mail bestätigen " placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email_confirmation" required />

    <x-inputs.phone right-icon="phone" label="Telefon"
                    mask="['### ### ## ##']" placeholder="079 123 45 67"
                    wire:model.blur="phone_number" required />

    <span class="space-y-1">
        <span class="text-gray-700 dark:text-gray-400 text-sm font-medium">Bist du volljährig?</span>
    <x-toggle wire:model.boolean="adult" left-label="Nein" label="Ja" lg />
        </span>

    <x-native-select label="Sportart" wire:model="sport_type_id"
                     hint="Das hilft uns bei der Organisation der Helfer:innen.">
        <option disabled value="0">Bitte auswählen</option>
        @if (!$sport_types || $sport_types->isEmpty())
            <option disabled value="0">Keine Sportarten verfügbar</option>
        @else
            @foreach ($sport_types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        @endif
    </x-native-select>

    <x-inputs.number right-icon="fire" label="Geschätzte Anzahl Runden" placeholder="11"
                     wire:model.blur="rounds_estimated"
                     hint="Das hilft deinen Spender:innen, den Betrag pro Runde festzulegen." required />

    <x-native-select label="Ich möchte sammeln für" wire:model="partner_id"
                     hint="Das Geld, das du mit deinen Spender:innen sammelst, geht an diese:n Benefizpartner:in.">
        <option disabled value="0">Bitte auswählen</option>
        @if (!$partners || $partners->isEmpty())
            <option disabled value="0">Keine Partner:innen verfügbar</option>
        @else
            @foreach ($partners as $partner)
                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
            @endforeach
        @endif
    </x-native-select>

    <x-textarea label="Kommentar"
                placeholder="Ich freu mich druf. Bin zwar nöd mega sportlich, aber das isch ja egal. Hauptsach es chunnt e gueti Summe zäme!"
                wire:model.live.debounce="comment" hint="{{ strlen($comment) }}/2000" />

    <span class="sm:col-span-2 flex flex-row space-x-sm items-center">
            <x-toggle wire:model.boolean="privacy" />
            <span class="text-md">
                Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden.
                <button type="button" wire:click="showPrivacyInfo"
                        class=" text-hfm-red">Was heisst das?</button>
            </span>
        </span>

    <x-honey />

    <span class="sm:col-span-2">
            <x-button label="Senden" type="submit" />
            <div wire:loading wire:target="save">
                Die Daten werden übermittelt...
            </div>
        </span>
</form>

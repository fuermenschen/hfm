<form wire:submit="save"
      class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    <x-input right-icon="user" label="Vorname" placeholder="Francesca" wire:model.blur="first_name" />

    <x-input right-icon="user" label="Nachname" placeholder="Arslan" wire:model.blur="last_name" />

    <x-input right-icon="home" label="Adresse" placeholder="Zelglistrasse 41" wire:model.blur="address" />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <x-inputs.maskable mask="####" right-icon="home" label="PLZ" placeholder="8406"
                                   wire:model.number.blur="zip_code"
                                   class="basis-1/3" />
            </span>
            <span class="grow">
                <x-input right-icon="home" label="Ort" placeholder="Winterthur" wire:model.blur="city"
                         class="grow" />
            </span>
        </span>

    <x-inputs.phone right-icon="phone" label="Telefon"
                    mask="['### ### ## ##']" placeholder="079 123 45 67"
                    wire:model.blur="phone_number" />

    <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email" />

    <x-input right-icon="cake" label="Alter" placeholder="47" wire:model.blur="age" />

    <x-native-select label="Sportart" wire:model="sport_type_id">
        <option disabled value="0">Bitte auswählen</option>
        @foreach ($sport_types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
    </x-native-select>

    <x-inputs.number right-icon="fire" label="Geschätzte Anzahl Runden" placeholder="11"
                     wire:model.blur="rounds_estimated" />

    <x-native-select label="Ich möchte sammeln für" wire:model="partner_id">
        <option disabled value="0">Bitte auswählen</option>
        @foreach ($partners as $partner)
            <option value="{{ $partner->id }}">{{ $partner->name }}</option>
        @endforeach
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

    <span class="sm:col-span-2">
            <x-button label=" Senden" type="submit" />
        </span>
</form>

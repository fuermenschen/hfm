<div>
    @component('components.page-title')
        Sponsor:in werden
    @endcomponent
Wir laden dich herzlich ein, Teil von "Höhenmeter für Menschen" zu werden. Du kannst deine gewünschte Sportler:in mit einem Geldbetrag pro Runde unterstützen. Die Sportlerin kann zwischen drei Benefizpartnern wählen: Brühlgut Stiftung, Kinderseele Schweiz, die Dargebotene Hand. Welche Wahl deine Sportler:in getroffen hat, siehst du im Anmeldeformular.

    <p></p>
Deine Spende wird zu 100% an die jeweiligen Benefizpartner weitergegeben. Der Round Table 25 Winterthur wird die Abwicklung durchführen. Du erhältst eine Rechnung mit einem Spendenausweis nach der Veranstaltung am 21.9.2024.

    <x-page-subtitle>
        Anmeldeformular
    </x-page-subtitle>
    Es freut uns, dass du Sponsor:in werden möchtest. Indem du das Formular ausfüllst, hilfst du den Sportler:innen,
    Spenden für unsere Benefizpartner:innen zu sammeln.
    <form wire:submit="save"
          class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

        <x-input right-icon="user" label="Vorname" placeholder="Francesca" wire:model.blur="form.first_name"/>

        <x-input right-icon="user" label="Nachname" placeholder="Arslan" wire:model.blur="form.last_name"/>

        <x-input right-icon="home" label="Adresse" placeholder="Zelglistrasse 41" wire:model.blur="form.address"/>

        <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <x-input right-icon="home" label="PLZ" placeholder="8406" wire:model.number.blur="form.zip_code"
                         class="basis-1/3"/>
            </span>
            <span class="grow">
                <x-input right-icon="home" label="Ort" placeholder="Winterthur" wire:model.blur="form.city"
                         class="grow"/>
            </span>
        </span>

        <x-inputs.phone right-icon="phone" label="Telefon"
                        mask="['### ### ## ##']" placeholder="079 123 45 67"
                        wire:model.blur="form.phone_number"/>

        <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
                 wire:model.blur="form.email"/>

        <span>
            <x-native-select label="Meine Unterstützung geht an" wire:model.live="form.athlete_id"
                             @change="$wire.updateNames()">
                <option disabled value="0">Bitte wählen</option>
                @foreach ($form->athletes as $athlete)
                    <option value="{{ $athlete['id'] }}">{{ $athlete['first_name'] }} {{ $athlete['last_name'] }}
                        ({{ $athlete['sponsoring_token'] }})
                    </option>
                @endforeach
            </x-native-select>
            @if ($form->currentAthlete)
                <span
                    class="text-xs">Mit deiner Unterstützung für <strong> {{ $form->currentAthlete }} </strong> hilfst du, Spenden für <strong>{{ $form->currentPartner }} </strong> zu sammeln. Danke!</span>
            @endif
        </span>

        <span>
            <x-inputs.currency
                label="Dein Beitrag pro Runde"
                placeholder="1.25" wire:model.number.blur="form.amount_per_round" prefix="Fr."/>
            <button type="button" wire:click="showAmountInfo"
                    class="text-xs text-hfm-dark">Wie funktioniert das?</button>
        </span>

        <span>
        <x-inputs.currency
            label="Dein minimaler Beitrag"
            placeholder="50" wire:model.number.blur="form.amount_min" prefix="Fr."/>
            <button type="button" wire:click="showAmountInfo"
                    class="text-xs text-hfm-dark">Wie funktioniert das?</button>
        </span>

        <span>
            <x-inputs.currency
                label="Dein maximaler Beitrag"
                placeholder="500" wire:model.number.blur="form.amount_max" prefix="Fr."/>
            <button type="button" wire:click="showAmountInfo"
                    class="text-xs text-hfm-dark">Wie funktioniert das?</button>
        </span>

        <x-textarea label="Kommentar"
                    placeholder="Ich freu mich druf. Bin zwar nöd mega sportlich, aber das isch ja egal. Hauptsach es chunnt e gueti Summe zäme!"
                    wire:model.live.debounce="form.comment" hint="{{ strlen($form->comment) }}/2000"/>

        <span class="sm:col-span-2 flex flex-row space-x-sm items-center">
            <x-toggle wire:model.boolean="form.privacy"/>
            <span class="text-md">
                Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden.
                <button type="button" wire:click="showPrivacyInfo"
                        class=" text-hfm-red">Was heisst das?</button>
            </span>
        </span>

        <span class="sm:col-span-2">
            <x-button label=" Senden" type="submit"/>
        </span>
    </form>

</div>

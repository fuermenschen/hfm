<div>
    @component('components.page-title')
        Sportler:in werden
    @endcomponent
Dein Einsatz für ein Lächeln – Lauf, fahre, rolle für Hilfe, die zu 100% ankommt! Wir laden dich herzlich ein, Teil dieses besonderen Sponsorenlaufs zu sein, bei dem deine Leidenschaft für Sport mit einem guten Zweck verbunden wird.
Du legst die Strecke im Brühlbergquartier so oft zurück wie möglich und sammelst dabei für jede Runde Spendengelder deiner Sponsoren für den Benefizpartner deiner Wahl. Du kannst selbst bestimmen, wie du die Strecke zurücklegst, wichtig ist nur, dass du die Strecke mit eigener Kraft zurücklegst. Mögliche Optionen sind Joggen, Gehen, Fahrradfahren, Skaten oder die Strecke mit dem Rollstuhl zurücklegen.
Alle sind willkommen: Egal ob Profi oder Gelegenheitssportler, Jung oder Alt, Laufend, gehend, auf dem Fahrrad oder im Rollstuhl - bei "Höhenmeter für Menschen" ist jeder willkommen, der sich für unsere Benefizpartner engagieren möchte.
Strecke und Spenden: Die Strecke umfasst 1.7 km und 50 Höhenmeter. Für jede Runde, die du absolvierst, sammelt du Spenden von euren großzügigen Sponsoren. Jede Runde zählt und macht einen Unterschied!
Verpflegung am Start: Damit du bestens gestärkt in den Event starten kannst, bieten wir euch Verpflegung am Startpunkt an. Tankt Energie und genießt die Gemeinschaft!
Fragen? Bei Fragen stehen wir gerne unter <a href="mailto:members@rt25.ch">members@rt25.ch</a> zur Verfügung.

    <x-page-subtitle>
        Anmeldeformular
    </x-page-subtitle>
    Es freut uns, dass du als Sportler:in bei uns mitmachen möchtest. Bitte fülle das Formular aus, damit wir alle
    nötigen Informationen zu dir haben.
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

        <x-native-select label="Sportart" wire:model="form.sport_type_id">
            <option disabled value="0">Bitte auswählen</option>
            @foreach ($form->sport_types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </x-native-select>

        <x-input right-icon="cake" label="Alter" placeholder="47" wire:model.blur="form.age"/>

        <x-native-select label="Ich möchte sammeln für" wire:model="form.partner_id">
            <option disabled value="0">Bitte auswählen</option>
            @foreach ($form->partners as $partner)
                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
            @endforeach
        </x-native-select>

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

<div>
    @component('components.page-title')
        Sportler:in werden
    @endcomponent
    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci alias amet animi aperiam aspernatur atque autem,
    beatae commodi consequatur corporis cumque delectus doloremque doloribus ducimus ea eius eligendi eos error est eum
    ex explicabo facere fugiat fugit harum id illum impedit in incidunt ipsa ipsam ipsum iure laborum laudantium magnam
    magni maiores maxime minima minus molestias mollitia natus nemo nesciunt nihil nisi nobis non nostrum numquam
    obcaecati odio officiis omnis optio pariatur perferendis perspiciatis placeat possimus praesentium provident quae
    quam quas qui quia quibusdam quisquam quo ratione recusandae rem repellendus repudiandae rerum saepe sapiente sequi
    similique sit soluta sunt suscipit tempora tenetur totam ullam unde vel veniam veritatis voluptas voluptates
    voluptatum.

    <x-page-subtitle>
        Anmeldeformular
    </x-page-subtitle>
    Es freut uns, dass du als Sportler:in bei uns mitmachen möchtest. Bitte fülle das Formular aus, damit wir alle
    nötigen Informationen zu dir haben.
    <form wire:submit="save"
          class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

        <x-input right-icon="user" label="Name" placeholder="Francesca" wire:model.blur="form.first_name"/>

        <x-input right-icon="user" label="Nachname" placeholder="Arslan" wire:model.blur="form.last_name"/>

        <x-input right-icon="home" label="Adresse" placeholder="Zelglistrasse 11" wire:model.blur="form.address"/>

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

        <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@kinderthur.ch"
                 wire:model.blur="form.email"/>

        <x-native-select label="Sportart" wire:model="form.sport_type">
            <option disabled value="0">Bitte auswählen</option>
            @foreach ($form->sport_types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </x-native-select>

        <x-input right-icon="cake" label="Alter" placeholder="47" wire:model.blur="form.age"/>

        <x-textarea label="Kommentar"
                    placeholder="Ich freu mich druf. Bin zwar nöd mega sportlich, aber das isch ja egal. Hauptsach es chunnt e gueti Summe zäme!"
                    wire:model.live.debounce="form.comment" hint="{{ strlen($form->comment) }}/2000"/>

        <span class="sm:col-span-2">
            <x-toggle
                label="Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden."
                wire:model.boolean="form.privacy"/>
        </span>

        <span class="sm:col-span-2">
            <x-button label=" Senden" type="submit"/>
        </span>
    </form>
</div>

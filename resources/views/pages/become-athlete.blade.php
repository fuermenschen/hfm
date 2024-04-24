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

    <form wire:submit="save" class="max-w-96">
        <x-input right-icon="user" label="Name" placeholder="Dein Vorname" wire:model.blur="form.first_name"/>
        <x-input right-icon="user" label="Nachname" placeholder="Dein Nachname" wire:model.blur="form.last_name"/>
        <x-input label="Geburtsdatum" placeholder="TT.MM.JJJJ" wire:model.blur="form.date_of_birth"
                 type="date"/>
        <x-button label=" Senden" type="submit"/>
    </form>
</div>

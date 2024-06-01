<form wire:submit="save"
      class="flex flex-col w-96 max-w-full space-y-sm mt-sm sm:mx-auto items-stretch">

    @csrf

    <x-input right-icon="user" label="Dein Name" placeholder="Francesca"
             wire:model.blur="name" />

    <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email" />

    <x-textarea right-icon="chat" label="Deine Nachricht"
                placeholder="Hallo, kann man bei eurem Verein auch direkt mitmachen? lg Francesca."
                wire:model.blur="message" />

    <x-honey />

    <span class="sm:col-span-2">
            <x-button label="Nachricht senden" type="submit" spinner="save" />
        </span>
</form>

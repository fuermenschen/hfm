<form wire:submit="save"
      class="flex flex-col w-96 max-w-full space-y-6 mt-6 sm:mx-auto items-stretch">

    @csrf

    <flux:input
        icon-trailing="user"
        label="Dein Name"
        placeholder="Francesca"
        wire:model.blur="name"
        type="text"
        autocomplete="name"
    />

    <flux:input
        icon-trailing="envelope"
        label="E-Mail"
        placeholder="francesca.arslan@posteo.ch"
        wire:model.blur="email"
        type="email"
        autocomplete="email"
    />

    <flux:textarea
        label="Deine Nachricht"
        placeholder="Hallo, kann man bei eurem Verein auch direkt mitmachen? lg Francesca."
        wire:model.blur="message"
        autocomplete="off"
    />

    <span class="sm:col-span-2">
            <flux:button type="submit" icon="paper-airplane">Nachricht senden</flux:button>
        </span>
</form>

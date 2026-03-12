<form wire:submit="save"
      class="flex flex-col w-96 max-w-full space-y-sm mt-sm sm:mx-auto items-stretch">

    @csrf

    <flux:input
        icon-trailing="envelope"
        label="E-Mail"
        placeholder="francesca.arslan@posteo.ch"
        wire:model.blur="email"
        type="email"
        autocomplete="email"
    />

    <span class="sm:col-span-2">
            <flux:button type="submit" icon="paper-airplane">Login-Link erhalten</flux:button>
        </span>
</form>

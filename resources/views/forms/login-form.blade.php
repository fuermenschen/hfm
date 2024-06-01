<form wire:submit="save"
      class="flex flex-col w-96 max-w-full space-y-sm mt-sm sm:mx-auto items-stretch">

    @csrf

    <x-input right-icon="mail" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
             wire:model.blur="email" />

    <x-honey />

    <span class="sm:col-span-2">
            <x-button label="Login-Link erhalten" type="submit" spinner="save" />
        </span>
</form>

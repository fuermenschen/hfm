<div class="mt-6"
     x-data="{ registrationQueued: $wire.entangle('registrationQueued').live }"
     x-effect="if (registrationQueued) { $flux.modal('newsletter-registration-success').show() }">
    <flux:modal name="newsletter-registration-success" class="sm:w-full md:w-xl" :dismissible="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Danke für deine Anmeldung</flux:heading>
                <flux:text class="mt-1">Wir haben deine Anmeldung erhalten und verarbeiten sie jetzt.</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="primary" x-on:click="window.location.href = '{{ route('home') }}'">Zur Startseite</flux:button>
            </div>
        </div>
    </flux:modal>

    <form wire:submit="save"
          class="flex flex-col space-y-6 sm:grid sm:grid-cols-2 max-w-full sm:space-y-0 sm:gap-6">
        @csrf

        <x-honeypot livewire-model="extraFields" />

        <flux:input
            wire:model.blur="first_name"
            label="Vorname"
            icon-trailing="user"
            placeholder="Francesca"
            autocomplete="given-name"
            required
            type="text"
        />

        <div class="sm:col-span-2">
            <flux:input
                wire:model.blur="email"
                label="E-Mail"
                icon-trailing="envelope"
                placeholder="francesca.arslan@posteo.ch"
                autocomplete="email"
                required
                type="email"
            />
        </div>

        <div class="sm:col-span-2">
            <flux:input
                wire:model.blur="email_confirmation"
                label="E-Mail bestätigen"
                icon-trailing="envelope"
                placeholder="francesca.arslan@posteo.ch"
                autocomplete="off"
                required
                type="email"
            />
        </div>

        <flux:button icon="paper-airplane" label="Anmelden" type="submit" class="sm:col-span-2 justify-self-start">
            Anmelden
        </flux:button>
    </form>
</div>

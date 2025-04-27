<form wire:submit="submit"
      class="flex flex-col space-y-sm sm:grid sm:grid-cols-2 max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    <flux:input
        icon-trailing="user"
        label="Vorname"
        wire:model.blur="first_name"
        placeholder="Francesca"
        required
        autocomplete="given-name"
    />

    <flux:input
        icon-trailing="user"
        label="Nachname"
        wire:model.blur="last_name"
        placeholder="Arslan"
        required
        autocomplete="family-name"
    />

    <flux:input
        icon-trailing="building-office"
        label="Firma"
        wire:model.blur="company_name"
        placeholder="Zukunft GmbH"
        badge="optional"
        autocomplete="organization"
    />

    <span class="hidden sm:block">
        <!-- Placeholder for layout purposes -->
    </span>

    <flux:input
        icon-trailing="home"
        label="Adresse"
        placeholder="Zelglistrasse 41"
        wire:model.blur="address"
        required
        autocomplete="street-address"
    />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <flux:input
                    icon-trailing="home"
                    label="PLZ"
                    placeholder="8406"
                    wire:model.blur="zip_code"
                    class="basis-1/3"
                    required
                    autocomplete="postal-code"
                    mask="9999"
                />
            </span>
            <span class="grow">
                <flux:input
                    icon-trailing="home"
                    label="Ort"
                    placeholder="Winterthur"
                    wire:model.blur="city"
                    class="grow"
                    required
                    autocomplete="address-level2"
                />
            </span>
        </span>

    <flux:input
        icon-trailing="envelope"
        label="E-Mail"
        placeholder="francesca.arslan@posteo.ch"
        wire:model.blur="email"
        required
        type="email"
        autocomplete="email"
    />

    <flux:input
        icon-trailing="envelope"
        label="E-Mail bestätigen"
        placeholder="francesca.arslan@posteo.ch"
        wire:model.blur="email_confirmation"
        required
        type="email"
        autocomplete="off"
    />

    <flux:input
        icon-trailing="phone"
        label="Telefon"
        placeholder="079 123 45 67"
        wire:model.blur="phone_number"
        required
        type="tel"
        autocomplete="tel"
    />

    <span>
        <!-- Placeholder for layout purposes -->
    </span>

    <flux:separator class="sm:col-span-2" />

    <flux:button
        type="submit"
        label="Absenden"
        variant="filled"
        icon="paper-airplane"
    >Senden
    </flux:button>
</form>

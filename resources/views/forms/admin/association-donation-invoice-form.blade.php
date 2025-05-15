<form wire:submit="submit"
      class="flex flex-col space-y-sm md:grid md:grid-cols-2 max-w-full md:space-y-0 md:gap-sm mt-sm">

    <flux:input
        label="Vorname"
        wire:model.blur="first_name"
        placeholder="Francesca"
        required
        autocomplete="given-name"
    />

    <flux:input
        label="Nachname"
        wire:model.blur="last_name"
        placeholder="Arslan"
        required
        autocomplete="family-name"
    />

    <flux:input
        label="Firma"
        wire:model.blur="company_name"
        placeholder="Zukunft GmbH"
        badge="optional"
        autocomplete="organization"
    />

    <flux:input
        label="Betrag"
        wire:model.blur="amount"
        placeholder="100.00"
        type="number"
        badge="optional" />

    <flux:separator class="sm:col-span-2" />

    <flux:input
        label="Adresse"
        placeholder="Zelglistrasse 41"
        wire:model.blur="address"
        required
        autocomplete="street-address"
    />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <flux:input
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
                    label="Ort"
                    placeholder="Winterthur"
                    wire:model.blur="city"
                    class="grow"
                    autocomplete="address-level2"
                />
            </span>
        </span>


    <flux:button
        type="submit"
        label="Absenden"
        variant="filled"
        icon="paper-airplane"
    >Spendenrechnung erstellen
    </flux:button>
</form>

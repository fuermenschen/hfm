<flux:card class="space-y-8 rounded-xl border-hfm-light/40 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div>
        <flux:heading size="lg" level="2">Kontaktdaten</flux:heading>
        <flux:text class="mt-1">Diese Angaben verwenden wir für die Kommunikation rund um deine Teilnahme oder Spende.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-8">
        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input :value="auth('external')->user()?->first_name" label="Vorname" icon-trailing="user" variant="filled" readonly />
            <flux:input :value="auth('external')->user()?->last_name" label="Nachname" icon-trailing="user" variant="filled" readonly />
            <flux:input :value="auth('external')->user()?->email" label="E-Mail" icon-trailing="envelope" type="email" variant="filled" readonly />
            <flux:input :value="match (auth('external')->user()?->country_of_residence) { 'DE' => 'Deutschland', 'AT' => 'Österreich', default => 'Schweiz' }" label="Wohnland" icon-trailing="globe-europe-africa" variant="filled" readonly />
            <flux:input :value="auth('external')->user()?->public_id_string" label="Öffentliche ID" icon-trailing="identification" variant="filled" readonly />

            <flux:callout icon="information-circle" variant="secondary" class="sm:col-span-2">
                <flux:callout.text>
                    Für Änderungen an deinem Namen, deiner E-Mail-Adresse oder deinem Wohnland kontaktiere uns bitte über <a href="{{ route('contact') }}" wire:navigate class="font-medium underline">Hilfe & Kontakt</a>.
                </flux:callout.text>
            </flux:callout>

            <flux:input wire:model.live.blur="address" label="Adresse" placeholder="Zelglistrasse 41" icon-trailing="home" autocomplete="street-address" required class="sm:col-span-2" />

            <flux:field class="sm:col-span-2">
                <flux:label>PLZ / Ort</flux:label>

                <flux:input.group style="display: flex; width: 100%;">
                    <div data-flux-input style="flex: 0 0 33.333333%;">
                        <flux:input wire:model.live.blur="zip_code" :mask="auth('external')->user()?->country_of_residence === 'DE' ? '99999' : '9999'" :placeholder="auth('external')->user()?->country_of_residence === 'DE' ? '57123' : '8406'" autocomplete="postal-code" required />
                    </div>
                    <div data-flux-input style="flex: 0 0 66.666667%;">
                        <flux:input wire:model.live.blur="city" placeholder="Winterthur" autocomplete="address-level2" required />
                    </div>
                </flux:input.group>

                <flux:error name="zip_code" />
                <flux:error name="city" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <flux:label>Telefon</flux:label>

                <flux:input.group style="display: flex; width: 100%;">
                    <div data-flux-input style="flex: 0 0 33.333333%;">
                        <flux:input :value="match (auth('external')->user()?->country_of_residence) { 'DE' => '+49', 'AT' => '+43', default => '+41' }" aria-label="Ländervorwahl" variant="filled" readonly />
                    </div>
                    <div data-flux-input style="flex: 0 0 66.666667%;">
                        <flux:input wire:model.live.blur="phone_national" :placeholder="match (auth('external')->user()?->country_of_residence) { 'DE' => '151 23456789', 'AT' => '650 1234567', default => '79 123 45 67' }" autocomplete="tel-national" type="tel" required />
                    </div>
                </flux:input.group>

                <flux:error name="phone_national" />
            </flux:field>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="check">Speichern</flux:button>
        </div>
    </form>
</flux:card>

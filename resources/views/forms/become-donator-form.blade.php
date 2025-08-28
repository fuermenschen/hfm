<form wire:submit="save"
      class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    @csrf

    <flux:input icon-trailing="user" label="Vorname" placeholder="Francesca" wire:model.blur="first_name" type="text"
                 autocomplete="given-name" required
    />

    <flux:input icon-trailing="user" label="Nachname" placeholder="Arslan" wire:model.blur="last_name" type="text"
                 autocomplete="family-name" required
    />

    <flux:input icon-trailing="home" label="Adresse" placeholder="Zelglistrasse 41" wire:model.blur="address" type="text"
                 autocomplete="street-address" required
    />

    <span class="flex flex-row space-x-4">
            <span class="basis-1/3">
                <flux:input icon-trailing="home" label="PLZ" placeholder="8406" mask="9999" wire:model.blur="zip_code"
                            class="basis-1/3" type="text" autocomplete="postal-code" required
                />
            </span>
            <span class="grow">
                <flux:input icon-trailing="home" label="Ort" placeholder="Winterthur" wire:model.blur="city"
                            class="grow" type="text" autocomplete="address-level2" required
                />
            </span>
        </span>

    <flux:input icon-trailing="envelope" label="E-Mail" placeholder="francesca.arslan@posteo.ch"
                 wire:model.blur="email" type="email" autocomplete="email" required
    />

    <flux:input icon-trailing="envelope" label="E-Mail bestätigen" placeholder="francesca.arslan@posteo.ch"
                 wire:model.blur="email_confirmation" type="email" autocomplete="off" required
    />

    <flux:input icon-trailing="phone" label="Telefon" mask="999 999 99 99" placeholder="079 123 45 67"
                 wire:model.blur="phone_number" type="tel" autocomplete="tel" required
    />

    <span>
    <flux:select wire:model.live="athlete_id" label="Meine Unterstützung geht an" autocomplete="off" @change="$wire.updateNames()" >
        <option disabled value="0">Bitte wählen</option>
        @if (!$athletes || sizeof($athletes) === 0)
            <option disabled value="0">Keine Sportler:innen verfügbar</option>
        @else
            @foreach ($athletes as $athlete)
                <option value="{{ $athlete['id'] }}">{{ $athlete['privacy_name'] }} ({{ $athlete['public_id_string'] }})
                </option>
            @endforeach
        @endif
    </flux:select>
    @if ($athlete_id)
        <span class="text-xs">Mit deiner Unterstützung für <strong>{{ $currentAthlete }}</strong> hilfst du, Spenden für
            <strong>{{ $currentPartner }}</strong> zu sammeln. Danke!</span>
    @endif
        </span>

    <span>
    <flux:input label="Dein Beitrag pro Runde" placeholder="7.25" wire:model.number.blur="amount_per_round" prefix="Fr."
                 type="number" step="0.01" autocomplete="off" required
    />
    @if ($athlete_id)
        <span class="text-xs"><strong>{{ $currentAthlete }}</strong> hat angegeben, ungefähr <strong>{{ $currentRounds }}</strong> Runden zu absolvieren.
                </span>
    @endif
        </span>

    <span>
    <flux:input label="Dein minimaler Beitrag (optional)" placeholder="50" wire:model.number.blur="amount_min" prefix="Fr."
                 type="number" step="0.01" autocomplete="off" />
    <button type="button" wire:click="showAmountInfo" class="text-xs underline">Wie funktioniert das?</button>
        </span>

    <span>
    <flux:input label="Dein maximaler Beitrag (optional)" placeholder="50" wire:model.number.blur="amount_max" prefix="Fr."
                 type="number" step="0.01" autocomplete="off" />
    <button type="button" wire:click="showAmountInfo" class="text-xs underline">Wie funktioniert das?</button>
        </span>

    <flux:textarea label="Kommentar"
                   placeholder="Cooli Sach! Ich tuen vil lieber d Claudia unterstütze und aafüfüre als selber Velofahre =)."
                   wire:model.live.debounce="comment" hint="{{ strlen($comment) }}/2000" autocomplete="off" />

    <span class="sm:col-span-2">
            <x-toggle wire:model.bool.live="privacy"
                      label="Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden." />
                <button type="button" wire:click="showPrivacyInfo"
                        class="text-xs underline mt-xs">Was heisst das?</button>
    </span>

    <x-honey />


    <x-button label="Senden" type="submit" spinner="save" class="justify-self-start" />
</form>

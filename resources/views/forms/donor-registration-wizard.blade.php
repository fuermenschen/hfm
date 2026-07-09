<div
    class="mt-8 w-full"
    x-data
    x-on:donor-registration-wizard-step-changed.window="$nextTick(() => $refs.card.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
>
    <div x-ref="card" class="w-full scroll-mt-6 overflow-hidden border-y border-zinc-200 dark:border-zinc-700">
        <x-honeypot livewire-model="extraFields" />

        <div class="border-b border-zinc-200 py-5 dark:border-zinc-700">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    @if ($displaySteps !== [])
                        <flux:text size="sm" class="font-medium text-hfm-red dark:text-hfm-lightred">
                            Schritt {{ $currentDisplayStepNumber }} von {{ count($displaySteps) }}
                        </flux:text>
                    @endif
                    <flux:heading size="lg" class="mt-1">
                        {{ $currentStepTitle }}
                    </flux:heading>
                    <flux:text class="mt-2 max-w-xl">
                        {{ $currentStepDescription }}
                    </flux:text>
                </div>

                <flux:badge color="red" class="w-fit">Spende</flux:badge>
            </div>

            @if ($displaySteps !== [])
                <div class="mt-5 space-y-3">
                    <flux:progress value="{{ $progressValue }}" class="h-2" />

                    <div class="hidden gap-2 sm:flex">
                        @foreach ($displaySteps as $key => $label)
                            @php
                                $isCurrent = $key === $currentStep;
                                $isAvailable = ! in_array($currentStep, ['login-link-sent', 'submitted'], true) && $loop->iteration <= $currentDisplayStepNumber && $key !== 'submitted';
                            @endphp

                            <button
                                type="button"
                                @if ($isAvailable) wire:click="goTo('{{ $key }}')" @endif
                                @disabled(! $isAvailable)
                                class="flex min-w-0 flex-1 items-center gap-2 rounded-full border px-3 py-2 text-left text-xs transition {{ $isCurrent ? 'border-hfm-red bg-hfm-red/10 text-hfm-red dark:border-hfm-lightred dark:bg-hfm-lightred/10 dark:text-hfm-lightred' : 'border-zinc-200 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300' }} {{ $isAvailable ? 'cursor-pointer hover:border-hfm-red/60 hover:text-hfm-red dark:hover:border-hfm-lightred/60 dark:hover:text-hfm-lightred' : 'cursor-not-allowed opacity-50' }}"
                            >
                                <span class="flex size-5 shrink-0 items-center justify-center rounded-full {{ $isCurrent ? 'bg-hfm-red text-white dark:bg-hfm-lightred dark:text-hfm-dark' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                    {{ $loop->iteration }}
                                </span>
                                <span class="truncate">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="py-6 sm:py-8" wire:key="donor-registration-wizard-{{ $currentStep }}">
            @if ($errors->any())
                <flux:callout icon="exclamation-triangle" variant="danger" class="mb-6">
                    <flux:callout.heading>Anmeldung noch nicht möglich</flux:callout.heading>
                    <flux:callout.text>{{ $errors->first() }}</flux:callout.text>
                </flux:callout>
            @endif

            @if ($currentStep === 'start')
                <div class="space-y-6">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:input
                            wire:model.live.blur="returning_email"
                            label="E-Mail-Adresse"
                            placeholder="francesca.arslan@posteo.ch"
                            description:trailing="Wenn wir dich kennen, schicken wir dir damit einen sicheren Link zurück in die Anmeldung."
                            icon-trailing="envelope"
                            type="email"
                            autocomplete="email"
                            required
                        />

                        <flux:input
                            wire:model.live.blur="returning_email_confirmation"
                            label="E-Mail bestätigen"
                            placeholder="francesca.arslan@posteo.ch"
                            icon-trailing="envelope"
                            type="email"
                            autocomplete="off"
                            required
                        />
                    </div>

                    <flux:callout wire:loading.flex wire:target="next" icon="shield-check" variant="secondary" class="w-full sm:col-span-2">
                        <flux:callout.heading>Wir prüfen, ob wir deine E-Mail-Adresse bereits im System haben...</flux:callout.heading>
                        <flux:callout.text>Wir verzögern diese Prüfung absichtlich kurz, damit bestehende Profile besser geschützt sind.</flux:callout.text>
                    </flux:callout>
                </div>
            @elseif ($currentStep === 'login-link-sent')
                <div class="grid min-h-80 place-items-center text-center">
                    <div class="max-w-lg space-y-5">
                        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-hfm-red/10 text-hfm-red dark:bg-hfm-lightred/10 dark:text-hfm-lightred">
                            <flux:icon.envelope class="size-8" />
                        </div>
                        <div>
                            <flux:heading size="lg">Login-Link verschickt</flux:heading>
                            <flux:text class="mt-2">Wir haben dir einen Link geschickt. Er bringt dich zurück zu dieser Anmeldung.</flux:text>
                        </div>
                        <flux:button variant="ghost" wire:click="goTo('start')">Andere E-Mail-Adresse verwenden</flux:button>
                    </div>
                </div>
            @elseif ($currentStep === 'personal')
                @if ($participation === 'returning')
                    <flux:callout icon="check-circle" variant="success">
                        <flux:callout.heading>Bekannte Angaben werden wiederverwendet</flux:callout.heading>
                        <flux:callout.text>
                            Nach dem Login überspringen wiederkehrende Spender:innen diesen Schritt. Persönliche Daten werden im Wizard nicht editiert.
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:input wire:model.live.blur="first_name" label="Vorname" placeholder="Francesca" icon-trailing="user" autocomplete="given-name" required />
                        <flux:input wire:model.live.blur="last_name" label="Nachname" placeholder="Arslan" icon-trailing="user" autocomplete="family-name" required />
                        <flux:input wire:model.live.blur="address" label="Adresse" placeholder="Zelglistrasse 41" icon-trailing="home" autocomplete="street-address" required class="sm:col-span-2" />

                        <flux:input wire:model.live.blur="zip_code" label="PLZ" :mask="$zipCodeMask" :placeholder="$zipCodePlaceholder" autocomplete="postal-code" icon-trailing="map-pin" required />

                        <flux:input wire:model.live.blur="city" label="Ort" placeholder="Winterthur" icon-trailing="map-pin" autocomplete="address-level2" required />

                        <flux:select wire:model.live="country_of_residence" label="Land" class="sm:col-span-2">
                            <flux:select.option value="CH">Schweiz</flux:select.option>
                            <flux:select.option value="DE">Deutschland</flux:select.option>
                            <flux:select.option value="AT">Österreich</flux:select.option>
                        </flux:select>

                        <flux:input wire:model.live.blur="phone_national" label="Telefon" :placeholder="$phonePlaceholder" icon-trailing="phone" autocomplete="tel" type="tel" required class="sm:col-span-2" />

                        <flux:select wire:model.live="phone_country" label="Ländervorwahl" class="sm:col-span-2">
                            <flux:select.option value="CH">Schweiz (+41)</flux:select.option>
                            <flux:select.option value="DE">Deutschland (+49)</flux:select.option>
                            <flux:select.option value="AT">Österreich (+43)</flux:select.option>
                        </flux:select>

                        <flux:input wire:model.live.blur="email" label="E-Mail" placeholder="francesca.arslan@posteo.ch" icon-trailing="envelope" autocomplete="email" type="email" required />
                        <flux:input wire:model.live.blur="email_confirmation" label="E-Mail bestätigen" placeholder="francesca.arslan@posteo.ch" icon-trailing="envelope" autocomplete="off" type="email" required />
                    </div>
                @endif
            @elseif ($currentStep === 'donation')
                <div class="space-y-7">
                    @if ($isAuthenticatedExternalUser && $externalUser)
                        <flux:callout icon="user-circle" variant="secondary">
                            <flux:callout.heading>Bestehendes Profil erkannt</flux:callout.heading>
                            <flux:callout.text>
                                Du meldest dich als {{ $externalUser->full_name }} mit {{ $externalUser->email }} an.
                            </flux:callout.text>
                        </flux:callout>
                    @endif

                    @if ($athleteRegistrations === [])
                        <flux:callout icon="exclamation-triangle" variant="warning">
                            <flux:callout.heading>Keine Sportler:innen verfügbar</flux:callout.heading>
                            <flux:callout.text>Aktuell sind noch keine Sportler:innen für diesen Anlass angemeldet.</flux:callout.text>
                        </flux:callout>
                    @else
                        <flux:select wire:model.live="athlete_registration_id" label="Ich unterstütze" variant="listbox" searchable placeholder="Sportler:in suchen..." empty="Keine Sportler:innen gefunden">
                            <x-slot name="search">
                                <flux:select.search class="px-4" placeholder="suchen..." />
                            </x-slot>

                            @foreach ($athleteRegistrations as $registration)
                                <flux:select.option wire:key="athlete-{{ $registration['id'] }}" value="{{ $registration['id'] }}" label="{{ $registration['display_name'] }}" data-test="athlete-option-{{ $registration['id'] }}">
                                    <span class="font-medium">{{ $registration['privacy_name'] }}</span>
                                    <span class="font-light">&nbsp;({{ $registration['public_id_string'] }})</span>
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        @if ($currentAthleteName)
                            <flux:callout icon="information-circle" variant="secondary">
                                <flux:callout.heading>{{ $currentAthleteName }}</flux:callout.heading>
                                <flux:callout.text>
                                    <strong>{{ $currentSportType }}</strong> · {{ $currentPartner }}<br>
                                    Geschätzte Runden: <strong>{{ $currentRounds }}</strong>
                                </flux:callout.text>
                            </flux:callout>
                        @endif
                    @endif

                    <flux:input
                        wire:model.live.blur="amount_per_round"
                        label="Dein Beitrag pro Runde"
                        description:trailing="Dieser Betrag wird mit der Anzahl Runden multipliziert, die {{ $currentAthleteName ?? 'die Sportler:in' }} absolviert."
                        placeholder="7.25"
                        prefix="Fr."
                        type="number"
                        step="0.01"
                        min="0.05"
                        required
                    />

                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:input
                            wire:model.live.blur="amount_min"
                            label="Minimaler Beitrag (optional)"
                            description:trailing="Dein Beitrag wird nie unter diesem Betrag liegen."
                            placeholder="50"
                            prefix="Fr."
                            type="number"
                            step="0.01"
                            min="0.05"
                        />

                        <flux:input
                            wire:model.live.blur="amount_max"
                            label="Maximaler Beitrag (optional)"
                            description:trailing="Dein Beitrag wird nie über diesem Betrag liegen."
                            placeholder="500"
                            prefix="Fr."
                            type="number"
                            step="0.01"
                            min="1"
                        />
                    </div>

                    <flux:callout icon="question-mark-circle" variant="secondary">
                        <flux:callout.heading>Wie funktioniert das?</flux:callout.heading>
                        <flux:callout.text>
                            Der Betrag, den du pro Runde spendest, wird mit der Anzahl Runden multipliziert, die {{ $currentAthleteName ?? 'die Sportler:in' }} absolviert. Falls sehr viele oder sehr wenige Runden absolviert werden, wird der Betrag auf das Minimum oder Maximum angepasst. Nach dem Anlass stellen wir dir eine Rechnung. Der Betrag geht zu <strong>100%</strong> an {{ $currentPartner ?? 'die Benefizpartner:in' }}.
                        </flux:callout.text>
                    </flux:callout>

                    <flux:textarea
                        wire:model.live.blur="comment"
                        label="Kommentar"
                        badge="optional"
                        description:trailing="Kurz, persönlich, motivierend. Maximal 2000 Zeichen."
                        placeholder="Cooli Sach! Ich tuen vil lieber d Claudia unterstütze und aafüüre als selber Velofahre =)."
                        rows="4"
                    />

                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:field variant="inline">
                            <flux:checkbox wire:model.live="privacy_accepted" />
                            <flux:label>Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden.</flux:label>
                            <flux:error name="privacy_accepted" />
                        </flux:field>
                    </div>
                </div>
            @elseif ($currentStep === 'submitted')
                <div class="grid min-h-80 place-items-center text-center">
                    <div class="max-w-lg space-y-5">
                        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-hfm-red/10 text-hfm-red dark:bg-hfm-lightred/10 dark:text-hfm-lightred">
                            <flux:icon.check class="size-8" />
                        </div>
                        <div>
                            <flux:heading size="lg">Anmeldung erhalten</flux:heading>
                            <flux:text class="mt-2">Wir haben dir eine E-Mail geschickt. Öffne dein Portal über den Link und bestätige dort deine Spende.</flux:text>
                        </div>
                        <flux:button variant="primary" wire:click="restart">
                            Weitere:n Sportler:in unterstützen
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>

        @if (! in_array($currentStep, ['submitted', 'login-link-sent'], true))
            <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 py-5 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                @if ($canGoBack)
                    <flux:button variant="ghost" icon="arrow-left" wire:click="back">
                        Zurück
                    </flux:button>
                @else
                    <span></span>
                @endif

                @if ($isFinalStep)
                    <flux:button variant="primary" icon:trailing="check" wire:click="submit" class="data-loading:opacity-50">
                        Anmeldung absenden
                    </flux:button>
                @else
                    <flux:button variant="primary" icon:trailing="arrow-right" wire:click="next" class="data-loading:opacity-50">
                        Weiter
                    </flux:button>
                @endif
            </div>
        @endif
    </div>
</div>

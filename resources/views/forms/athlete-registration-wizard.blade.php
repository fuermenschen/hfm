<div
    class="mt-8 w-full"
    x-data
    x-on:athlete-registration-wizard-step-changed.window="$nextTick(() => $refs.card.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
>
    <div x-ref="card" class="w-full scroll-mt-6 overflow-hidden border-y border-zinc-200 dark:border-zinc-700">
        <x-honeypot livewire-model="extraFields" />

        <div class="border-b border-zinc-200 py-5 dark:border-zinc-700">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <flux:text size="sm" class="font-medium text-hfm-red dark:text-hfm-lightred">
                        Schritt {{ $currentStepNumber }} von {{ count($steps) }}
                    </flux:text>
                    <flux:heading size="lg" class="mt-1">
                        @switch($currentStep)
                            @case('start')
                                Mit welcher E-Mail-Adresse möchtest du dich anmelden?
                                @break
                            @case('personal')
                                Deine Angaben
                                @break
                            @case('login-link-sent')
                                Bitte prüfe deine E-Mail
                                @break
                            @case('registration')
                                Dein Einsatz
                                @break
                            @case('previous-donors')
                                Frühere Spender:innen informieren?
                                @break
                            @case('submitted')
                                Anmeldung erhalten
                                @break
                        @endswitch
                    </flux:heading>
                    <flux:text class="mt-2 max-w-xl">
                        @switch($currentStep)
                            @case('start')
                                Wir prüfen, ob bereits ein Profil für dich existiert.
                                @break
                            @case('personal')
                                Neue Teilnehmer:innen erfassen ihre Kontaktdaten einmalig.
                                @break
                            @case('login-link-sent')
                                Wir haben dir einen Link geschickt. Er bringt dich zurück zu dieser Anmeldung.
                                @break
                            @case('registration')
                                @if ($isAuthenticatedExternalUser && $externalUser)
                                    Du meldest dich mit deinem bestehenden Profil als {{ $externalUser->full_name }} an.
                                @else
                                    Diese Angaben sehen Spender:innen später als Orientierung.
                                @endif
                                @break
                            @case('previous-donors')
                                Wir können Menschen informieren, die dich früher schon unterstützt haben.
                                @break
                            @case('submitted')
                                Bitte prüfe deine E-Mail und bestätige deine Anmeldung im Portal.
                                @break
                        @endswitch
                    </flux:text>
                </div>

                <flux:badge color="red" class="w-fit">Anmeldung</flux:badge>
            </div>

            <div class="mt-5 space-y-3">
                <flux:progress value="{{ $progressValue }}" class="h-2" />

                <div class="hidden gap-2 sm:flex">
                    @foreach ($steps as $key => $label)
                        @php
                            $isCurrent = $key === $currentStep;
                            $isAvailable = ! in_array($currentStep, ['login-link-sent', 'submitted'], true) && $loop->iteration <= $currentStepNumber && $key !== 'submitted';
                        @endphp

                        <button
                            type="button"
                            @if ($isAvailable) wire:click="goTo('{{ $key }}')" @else disabled @endif
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
        </div>

        <div class="py-6 sm:py-8" wire:key="athlete-registration-wizard-{{ $currentStep }}">
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
                            Nach dem Login überspringen wiederkehrende Teilnehmer:innen diesen Schritt. Persönliche Daten werden im Wizard nicht editiert.
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:input wire:model.live.blur="first_name" label="Vorname" placeholder="Francesca" icon-trailing="user" autocomplete="given-name" required />
                        <flux:input wire:model.live.blur="last_name" label="Nachname" placeholder="Arslan" icon-trailing="user" autocomplete="family-name" required />
                        <flux:input wire:model.live.blur="address" label="Adresse" placeholder="Zelglistrasse 41" icon-trailing="home" autocomplete="street-address" required class="sm:col-span-2" />

                        <flux:input wire:model.live.blur="zip_code" label="PLZ" mask="9999" placeholder="8406" autocomplete="postal-code" icon-trailing="map-pin" required />

                        <flux:input wire:model.live.blur="city" label="Ort" placeholder="Winterthur" icon-trailing="map-pin" autocomplete="address-level2" required />
                        <flux:input wire:model.live.blur="phone_number" label="Telefon" mask="999 999 99 99" placeholder="079 123 45 67" icon-trailing="phone" autocomplete="tel" type="tel" required class="sm:col-span-2" />
                        <flux:input wire:model.live.blur="email" label="E-Mail" placeholder="francesca.arslan@posteo.ch" icon-trailing="envelope" autocomplete="email" type="email" required />
                        <flux:input wire:model.live.blur="email_confirmation" label="E-Mail bestätigen" placeholder="francesca.arslan@posteo.ch" icon-trailing="envelope" autocomplete="off" type="email" required />
                    </div>
                @endif
            @elseif ($currentStep === 'registration')
                <div class="space-y-7">
                    @if ($isAuthenticatedExternalUser && $externalUser)
                        <flux:callout icon="user-circle" variant="secondary">
                            <flux:callout.heading>Bestehendes Profil erkannt</flux:callout.heading>
                            <flux:callout.text>
                                Du meldest dich als {{ $externalUser->full_name }} mit {{ $externalUser->email }} an.
                            </flux:callout.text>
                        </flux:callout>
                    @endif

                    @if ($sportTypes->isEmpty())
                        <flux:callout icon="exclamation-triangle" variant="warning" heading="Keine Sportarten verfügbar" />
                    @else
                        <flux:radio.group wire:model.live="sport_type_id" label="Sportart" variant="pills">
                            @foreach ($sportTypes as $sportType)
                                <flux:radio wire:key="sport-type-{{ $sportType->id }}" value="{{ $sportType->id }}" label="{{ $sportType->name }}" class="cursor-pointer" />
                            @endforeach
                        </flux:radio.group>
                    @endif

                    <flux:input
                        wire:model.live.blur="rounds_estimated"
                        label="Geschätzte Anzahl Runden"
                        description:trailing="Dies hilft Spender:innen beim Einschätzen ihres Beitrags pro Runde. Keine Sorge, du kannst auch weniger oder mehr Runden absolvieren."
                        placeholder="11"
                        icon-trailing="fire"
                        type="number"
                        min="1"
                        required
                    />

                    <flux:radio.group wire:model.live="partner_id" label="Ich sammle für" variant="cards" class="flex-col">
                        @if ($allowEqualSplitOption)
                            <flux:radio value="0" icon="scale" label="{{ ucfirst(__('app.equal_split')) }}" description="Meine Spenden werden gleichmässig auf alle Benefizpartner:innen verteilt." />
                        @endif

                        @forelse ($partners as $partner)
                            <flux:radio wire:key="partner-{{ $partner->id }}" value="{{ $partner->id }}" icon="heart" label="{{ $partner->name }}" description="Meine Spenden gehen an diese Organisation." />
                        @empty
                            <flux:radio disabled value="-1" label="Keine Partner:innen verfügbar" />
                        @endforelse
                    </flux:radio.group>

                    <flux:textarea
                        wire:model.live.blur="comment"
                        label="Kommentar"
                        badge="optional"
                        description:trailing="Kurz, persönlich, motivierend. Maximal 2000 Zeichen."
                        placeholder="Ich freu mich druf. Bin zwar nöd mega sportlich, aber das isch ja egal. Hauptsach es chunnt e gueti Summe zäme!"
                        rows="4"
                    />

                    @unless ($hasPreviousDonors)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:field variant="inline">
                                <flux:checkbox wire:model.live="privacy_accepted" />
                                <flux:label>Ich bin damit einverstanden, dass meine Daten für die Organisation des Anlasses verwendet werden.</flux:label>
                                <flux:error name="privacy_accepted" />
                            </flux:field>
                        </div>
                    @endunless
                </div>
            @elseif ($currentStep === 'previous-donors')
                <div class="space-y-6">
                    <flux:callout icon="megaphone" color="green">
                        <flux:callout.heading>Frühere Unterstützer:innen aktivieren</flux:callout.heading>
                        <flux:callout.text>
                            Nach deiner Bestätigung werden frühere Spender:innen informiert, dass du wieder teilnimmst.
                        </flux:callout.text>
                    </flux:callout>

                    <flux:field variant="inline">
                        <flux:checkbox wire:model.live="notify_previous_donors" />
                        <flux:label>Frühere Spender:innen informieren</flux:label>
                        <flux:error name="notify_previous_donors" />
                    </flux:field>

                    @if (! $notify_previous_donors)
                        <flux:callout icon="exclamation-triangle" variant="warning">
                            <flux:callout.heading>Hinweis</flux:callout.heading>
                            <flux:callout.text>Ohne Hinweis an frühere Spender:innen kann es länger dauern, bis erste Spenden eintreffen.</flux:callout.text>
                        </flux:callout>
                    @endif

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
                            <flux:text class="mt-2">Wir haben dir eine E-Mail geschickt. Öffne dein Portal über den Link und bestätige dort deine Anmeldung.</flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if (! in_array($currentStep, ['submitted', 'login-link-sent'], true))
            <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 py-5 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                @if ($currentStepNumber > 1)
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

<form wire:submit="save" class="space-y-6">
    @if ($errors->any())
        <flux:callout
            variant="danger"
            icon="exclamation-triangle"
            heading="Bitte überprüfe deine Eingaben"
            tabindex="-1"
            x-init="
                $nextTick(() => {
                    const field = $el.parentElement.querySelector('[aria-invalid=true]');
                    (field ?? $el).scrollIntoView({ behavior: 'smooth', block: 'center' });
                    (field ?? $el).focus();
                })
            "
        >
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Allgemein</flux:heading>
            <flux:subheading>Identifikation und Veröffentlichung des Anlasses.</flux:subheading>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Pflichtfeld">Titel</flux:label>
                    <x-admin.field-info
                        label="Titel"
                        text="Erscheint als Haupttitel im Hero der Startseite und wird in anlassbezogenen Bestätigungen verwendet."
                    />
                </div>
                <flux:input wire:model="form.title" required />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Pflichtfeld">Slug</flux:label>
                    <x-admin.field-info
                        label="Slug"
                        text="Eindeutige, flexible Kennung des Anlasses für Admin-Tabelle und Exporte."
                    />
                </div>
                <flux:input wire:model="form.slug" placeholder="hfm-2027" required />
                <flux:error name="form.slug" />
            </flux:field>

            @if ($isCurrentEvent)
                <flux:callout
                    class="sm:col-span-2"
                    variant="danger"
                    icon="exclamation-triangle"
                    x-cloak
                    x-show="! $wire.form.is_published"
                >
                    Dieser Anlass ist als aktueller Anlass gesetzt. Beim Aufheben der Veröffentlichung bleiben
                    öffentliche Event-Inhalte und Anmeldungen geschlossen.
                </flux:callout>
            @endif

            <flux:field variant="inline">
                <flux:switch wire:model="form.is_published" />
                <div class="flex items-center gap-1">
                    <flux:label>Veröffentlicht</flux:label>
                    <x-admin.field-info
                        label="Veröffentlicht"
                        text="Nur ein veröffentlichter Anlass kann als aktueller Anlass auf öffentlichen Seiten und in den Anmeldungen verwendet werden."
                    />
                </div>
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="form.has_equal_split_option" />
                <div class="flex items-center gap-1">
                    <flux:label>Gleichmässige Spendenaufteilung anbieten</flux:label>
                    <x-admin.field-info
                        label="Gleichmässige Spendenaufteilung"
                        text="Steuert, ob Sportler:innen ihre Spenden in der Anmeldung gleichmässig auf alle Benefizpartner:innen verteilen können."
                    />
                </div>
            </flux:field>
        </div>
    </flux:card>

    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Zeitplan</flux:heading>
            <flux:subheading>Alle Zeitangaben werden in der Zeitzone Europe/Zurich erfasst.</flux:subheading>
        </div>

        <flux:callout icon="information-circle">
            Leere Anmeldedaten halten die entsprechende Anmeldung geschlossen. Erst mit Anmeldestart und passendem
            Anmeldeschluss kann sie geöffnet werden.
        </flux:callout>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Pflichtfeld">Start</flux:label>
                    <x-admin.field-info
                        label="Start"
                        text="Startzeit des Anlasses. Das Datum erscheint zusammen mit der Stadt im Hero der Startseite."
                    />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.starts_at" required />
                <flux:error name="form.starts_at" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Pflichtfeld">Ende</flux:label>
                    <x-admin.field-info
                        label="Ende"
                        text="Endzeit des Anlasses. Sie wird derzeit in der Admin-Tabelle und in Exporten ausgegeben."
                    />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.ends_at" required />
                <flux:error name="form.ends_at" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">Anmeldung offen ab</flux:label>
                    <x-admin.field-info
                        label="Anmeldung offen ab"
                        text="Ab diesem Zeitpunkt können Anmeldungen geöffnet sein. Zusätzlich gilt der jeweilige Anmeldeschluss."
                    />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.registration_opens_at" />
                <flux:error name="form.registration_opens_at" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">Anmeldung Sportler:innen bis</flux:label>
                    <x-admin.field-info
                        label="Anmeldeschluss Sportler:innen"
                        text="Nach diesem Zeitpunkt ist die Anmeldung als Sportler:in für diesen Anlass geschlossen."
                    />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.athlete_registration_closes_at" />
                <flux:error name="form.athlete_registration_closes_at" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">Anmeldung Spender:innen bis</flux:label>
                    <x-admin.field-info
                        label="Anmeldeschluss Spender:innen"
                        text="Nach diesem Zeitpunkt ist die Anmeldung als Spender:in für diesen Anlass geschlossen."
                    />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.donor_registration_closes_at" />
                <flux:error name="form.donor_registration_closes_at" />
            </flux:field>
        </div>
    </flux:card>

    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Ort</flux:heading>
            <flux:subheading>Veranstaltungsort und Kartenlink.</flux:subheading>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">Name</flux:label>
                    <x-admin.field-info
                        label="Name des Veranstaltungsorts"
                        text="Bezeichnung des Veranstaltungsorts. Sie wird derzeit in der Admin-Tabelle und in Exporten verwendet."
                    />
                </div>
                <flux:input wire:model="form.location_name" />
                <flux:error name="form.location_name" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">Strasse</flux:label>
                    <x-admin.field-info
                        label="Strasse"
                        text="Strasse des Veranstaltungsorts. Sie wird derzeit in der Admin-Tabelle und in Exporten verwendet."
                    />
                </div>
                <flux:input wire:model="form.location_street" />
                <flux:error name="form.location_street" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">PLZ</flux:label>
                    <x-admin.field-info
                        label="PLZ"
                        text="Postleitzahl des Veranstaltungsorts. Sie wird derzeit in der Admin-Tabelle und in Exporten verwendet."
                    />
                </div>
                <flux:input wire:model="form.location_postal_code" inputmode="numeric" autocomplete="postal-code" />
                <flux:error name="form.location_postal_code" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label badge="Pflichtfeld">Stadt</flux:label>
                    <x-admin.field-info
                        label="Stadt"
                        text="Erscheint zusammen mit dem Startdatum im Hero der Startseite."
                    />
                </div>
                <flux:input wire:model="form.location_city" required />
                <flux:error name="form.location_city" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label badge="Optional">Kartenlink</flux:label>
                    <x-admin.field-info
                        label="Kartenlink"
                        text="Link zur Karte des Veranstaltungsorts. Er wird derzeit in der Admin-Tabelle und in Exporten verwendet."
                    />
                </div>
                <flux:input type="url" wire:model="form.location_url" placeholder="https://maps.example.org/..." />
                <flux:error name="form.location_url" />
            </flux:field>
        </div>
    </flux:card>

    <flux:card class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="lg">Öffentliche Inhalte</flux:heading>
                <flux:subheading>Unbekannte Inhaltsfelder bleiben beim Speichern erhalten.</flux:subheading>
            </div>

            <flux:modal.trigger name="donation-event-markdown-help">
                <flux:button type="button" variant="ghost" size="sm" icon="information-circle" icon:variant="outline"
                    >Markdown-Hilfe</flux:button>
            </flux:modal.trigger>
        </div>

        <div class="space-y-8">
            <section class="grid gap-4">
                <div>
                    <flux:heading>Startseite</flux:heading>
                    <flux:subheading>Hero und Informationsbereich der öffentlichen Startseite.</flux:subheading>
                </div>
                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">Hero-Text</flux:label>
                        <x-admin.field-info
                            label="Hero-Text"
                            text="Erscheint direkt unter dem Anlass-Titel im Hero der Startseite. Inline-Markdown für Hervorhebungen und Links wird unterstützt."
                        />
                    </div>
                    <flux:textarea
                        rows="3"
                        wire:model="form.content.hero.copy_md"
                        placeholder="Gemeinsam Höhenmeter sammeln und lokale Projekte unterstützen."
                    />
                    <flux:error name="form.content.hero.copy_md" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">Homepage-Überschrift</flux:label>
                        <x-admin.field-info
                            label="Homepage-Überschrift"
                            text="Erscheint als Überschrift im Informationsbereich unterhalb des Hero-Bereichs auf der Startseite."
                        />
                    </div>
                    <flux:input wire:model="form.content.home.about_heading" placeholder="Darum geht es" />
                    <flux:error name="form.content.home.about_heading" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">Homepage-Einleitung</flux:label>
                        <x-admin.field-info
                            label="Homepage-Einleitung"
                            text="Erscheint hervorgehoben direkt unter der Homepage-Überschrift. Markdown wird unterstützt."
                        />
                    </div>
                    <flux:textarea
                        rows="4"
                        wire:model="form.content.home.about_intro_md"
                        placeholder="Kurze Einführung zum Anlass und seinem Zweck."
                    />
                    <flux:error name="form.content.home.about_intro_md" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">Homepage-Haupttext</flux:label>
                        <x-admin.field-info
                            label="Homepage-Haupttext"
                            text="Erscheint im Informationsbereich der Startseite vor den Hinweisen zur Teilnahme. Markdown wird unterstützt."
                        />
                    </div>
                    <flux:textarea
                        rows="6"
                        wire:model="form.content.home.about_body_md"
                        placeholder="Weitere Informationen zu Teilnahme, Strecke und unterstützten Projekten."
                    />
                    <flux:error name="form.content.home.about_body_md" />
                </flux:field>
            </section>

            <flux:separator />

            <section class="grid gap-4">
                <div>
                    <flux:heading>Resultate</flux:heading>
                    <flux:subheading>Text für die öffentliche Resultate-Seite.</flux:subheading>
                </div>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">Resultate-Überschrift</flux:label>
                        <x-admin.field-info
                            label="Resultate-Überschrift"
                            text="Erscheint als Seitentitel auf der öffentlichen Resultate-Seite. Formatierung wird dort als reiner Text ausgegeben."
                        />
                    </div>
                    <flux:input wire:model="form.content.results.heading_md" />
                    <flux:error name="form.content.results.heading_md" />
                </flux:field>
            </section>

            <flux:separator />

            <section class="grid gap-4">
                <div>
                    <flux:heading>SEO / Teilen</flux:heading>
                    <flux:subheading>Beschreibungen für Suchmaschinen und soziale Netzwerke.</flux:subheading>
                </div>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">SEO-Beschreibung</flux:label>
                        <x-admin.field-info
                            label="SEO-Beschreibung"
                            text="Wird als Meta-Beschreibung für Suchmaschinen ausgegeben. Formatierung wird entfernt; nur Text bleibt bestehen."
                        />
                    </div>
                    <flux:textarea rows="3" wire:model="form.content.seo.meta_description_md" />
                    <flux:error name="form.content.seo.meta_description_md" />
                </flux:field>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">OpenGraph-Beschreibung</flux:label>
                        <x-admin.field-info
                            label="OpenGraph-Beschreibung"
                            text="Wird als Beschreibung beim Teilen der Website verwendet. Formatierung wird entfernt; nur Text bleibt bestehen."
                        />
                    </div>
                    <flux:textarea rows="3" wire:model="form.content.seo.og_description_md" />
                    <flux:error name="form.content.seo.og_description_md" />
                </flux:field>
            </section>

            <flux:separator />

            <section class="grid gap-4">
                <div>
                    <flux:heading>Rechnung</flux:heading>
                    <flux:subheading>Zusätzliche anlassbezogene Rechnungsinformation.</flux:subheading>
                </div>

                <flux:field>
                    <div class="flex items-center gap-1">
                        <flux:label badge="Optional">Zusatzinformation Spendenrechnung</flux:label>
                        <x-admin.field-info
                            label="Zusatzinformation Spendenrechnung"
                            text="Ist für anlassbezogene Rechnungstexte vorgesehen, wird derzeit aber noch nicht automatisch auf einer Rechnung ausgegeben."
                        />
                    </div>
                    <flux:textarea rows="3" wire:model="form.content.invoice.additional_information" />
                    <flux:error name="form.content.invoice.additional_information" />
                </flux:field>
            </section>
        </div>
    </flux:card>

    <flux:modal name="donation-event-markdown-help" class="space-y-6 md:w-xl">
        <div>
            <flux:heading size="lg">Markdown-Syntax</flux:heading>
            <flux:subheading>Für Hero-Text, Homepage-Einleitung und Homepage-Haupttext.</flux:subheading>
        </div>

        <div class="grid gap-3 text-sm sm:grid-cols-2">
            <code>**fett**</code><span><strong>fett</strong></span> <code>*kursiv*</code><span><em>kursiv</em></span>
            <code>[Linktext](https://example.org)</code><span>Link</span> <code>- Listeneintrag</code
            ><span>Aufzählung</span> <code>## Überschrift</code><span>Zwischenüberschrift</span> <code>Leerzeile</code
            ><span>Neuer Absatz</span>
        </div>

        <flux:callout icon="shield-check">
            HTML wird entfernt. Unsichere Links werden nicht ausgegeben. Im Hero funktionieren nur Inline-Formatierungen
            wie fett, kursiv und Links.
        </flux:callout>
    </flux:modal>

    @php
        $dirtyEventFields = [
            'form.title' => 'Titel',
            'form.slug' => 'Slug',
            'form.is_published' => 'Veröffentlichung',
            'form.has_equal_split_option' => 'Spendenaufteilung',
            'form.starts_at' => 'Start',
            'form.ends_at' => 'Ende',
            'form.registration_opens_at' => 'Anmeldestart',
            'form.athlete_registration_closes_at' => 'Anmeldeschluss Sportler:innen',
            'form.donor_registration_closes_at' => 'Anmeldeschluss Spender:innen',
            'form.location_name' => 'Ort',
            'form.location_street' => 'Strasse',
            'form.location_postal_code' => 'PLZ',
            'form.location_city' => 'Stadt',
            'form.location_url' => 'Kartenlink',
            'form.content.hero.copy_md' => 'Hero-Text',
            'form.content.home.about_heading' => 'Homepage-Überschrift',
            'form.content.home.about_intro_md' => 'Homepage-Einleitung',
            'form.content.home.about_body_md' => 'Homepage-Haupttext',
            'form.content.results.heading_md' => 'Resultate',
            'form.content.seo.meta_description_md' => 'SEO',
            'form.content.seo.og_description_md' => 'OpenGraph',
            'form.content.invoice.additional_information' => 'Rechnung',
        ];
    @endphp

    <div class="sticky bottom-0 z-20 flex items-center gap-3 border-t border-zinc-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <div x-cloak x-show="$wire.$dirty() || $wire.hasUnsavedChanges" class="space-y-1.5">
            <flux:text class="text-accent text-sm">Ungespeicherte Änderungen</flux:text>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($dirtyEventFields as $property => $label)
                    <flux:badge size="sm" x-cloak x-show="$wire.$dirty(@js($property))">{{ $label }}</flux:badge>
                @endforeach
                <flux:badge size="sm" x-cloak x-show="$wire.hasUnsavedChanges && ! $wire.$dirty()"
                    >Formularangaben</flux:badge>
            </div>
        </div>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span
                wire:loading.remove
                wire:target="save"
            >{{ $donationEvent === null ? 'Anlass erstellen' : 'Änderungen speichern' }}</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>

    <flux:modal name="confirm-current-event-unpublish" class="md:w-lg" :dismissible="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Aktuellen Anlass nicht mehr veröffentlichen?</flux:heading>
                <flux:text class="mt-2"
                    >Öffentliche Event-Inhalte und Anmeldungen bleiben geschlossen, solange dieser aktuelle Anlass nicht
                    veröffentlicht ist.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" wire:click="confirmUnpublished"
                    >Trotzdem speichern</flux:button>
            </div>
        </div>
    </flux:modal>
</form>

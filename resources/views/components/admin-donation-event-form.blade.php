<form wire:submit="save" class="space-y-6">
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Allgemein</flux:heading>
            <flux:subheading>Identifikation und Veröffentlichung des Anlasses.</flux:subheading>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Titel</flux:label>
                    <x-admin.field-info label="Titel" text="Erscheint als Haupttitel im Hero der Startseite und wird in anlassbezogenen Bestätigungen verwendet." />
                </div>
                <flux:input wire:model="form.title" />
                <flux:error name="form.title" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Kürzel</flux:label>
                    <x-admin.field-info label="Kürzel" text="Eindeutige interne Kennung des Anlasses. Sie erscheint als Jahr in der Admin-Tabelle und in Exporten." />
                </div>
                <flux:input wire:model="form.slug" placeholder="2027" />
                <flux:error name="form.slug" />
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="form.is_published" />
                <div class="flex items-center gap-1">
                    <flux:label>Veröffentlicht</flux:label>
                    <x-admin.field-info label="Veröffentlicht" text="Nur ein veröffentlichter Anlass kann als aktueller Anlass auf öffentlichen Seiten und in den Anmeldungen verwendet werden." />
                </div>
            </flux:field>

            <flux:field variant="inline">
                <flux:switch wire:model="form.has_equal_split_option" />
                <div class="flex items-center gap-1">
                    <flux:label>Gleichmässige Spendenaufteilung anbieten</flux:label>
                    <x-admin.field-info label="Gleichmässige Spendenaufteilung" text="Steuert, ob Sportler:innen ihre Spenden in der Anmeldung gleichmässig auf alle Benefizpartner:innen verteilen können." />
                </div>
            </flux:field>
        </div>
    </flux:card>

    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Zeitplan</flux:heading>
            <flux:subheading>Alle Zeitangaben werden in der Zeitzone Europe/Zurich erfasst.</flux:subheading>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Start</flux:label>
                    <x-admin.field-info label="Start" text="Startzeit des Anlasses. Das Datum erscheint zusammen mit der Stadt im Hero der Startseite." />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.starts_at" />
                <flux:error name="form.starts_at" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Ende</flux:label>
                    <x-admin.field-info label="Ende" text="Endzeit des Anlasses. Sie wird derzeit in der Admin-Tabelle und in Exporten ausgegeben." />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.ends_at" />
                <flux:error name="form.ends_at" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label>Anmeldung offen ab</flux:label>
                    <x-admin.field-info label="Anmeldung offen ab" text="Ab diesem Zeitpunkt können Anmeldungen geöffnet sein. Zusätzlich gilt der jeweilige Anmeldeschluss." />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.registration_opens_at" />
                <flux:error name="form.registration_opens_at" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Anmeldung Sportler:innen bis</flux:label>
                    <x-admin.field-info label="Anmeldeschluss Sportler:innen" text="Nach diesem Zeitpunkt ist die Anmeldung als Sportler:in für diesen Anlass geschlossen." />
                </div>
                <flux:input type="datetime-local" step="1" wire:model="form.athlete_registration_closes_at" />
                <flux:error name="form.athlete_registration_closes_at" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Anmeldung Spender:innen bis</flux:label>
                    <x-admin.field-info label="Anmeldeschluss Spender:innen" text="Nach diesem Zeitpunkt ist die Anmeldung als Spender:in für diesen Anlass geschlossen." />
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
                    <flux:label>Name</flux:label>
                    <x-admin.field-info label="Name des Veranstaltungsorts" text="Bezeichnung des Veranstaltungsorts. Sie wird derzeit in der Admin-Tabelle und in Exporten verwendet." />
                </div>
                <flux:input wire:model="form.location_name" />
                <flux:error name="form.location_name" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label>Strasse</flux:label>
                    <x-admin.field-info label="Strasse" text="Strasse des Veranstaltungsorts. Sie wird derzeit in der Admin-Tabelle und in Exporten verwendet." />
                </div>
                <flux:input wire:model="form.location_street" />
                <flux:error name="form.location_street" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>PLZ</flux:label>
                    <x-admin.field-info label="PLZ" text="Postleitzahl des Veranstaltungsorts. Sie wird derzeit in der Admin-Tabelle und in Exporten verwendet." />
                </div>
                <flux:input wire:model="form.location_postal_code" />
                <flux:error name="form.location_postal_code" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Stadt</flux:label>
                    <x-admin.field-info label="Stadt" text="Erscheint zusammen mit dem Startdatum im Hero der Startseite." />
                </div>
                <flux:input wire:model="form.location_city" />
                <flux:error name="form.location_city" />
            </flux:field>

            <flux:field class="sm:col-span-2">
                <div class="flex items-center gap-1">
                    <flux:label>Kartenlink</flux:label>
                    <x-admin.field-info label="Kartenlink" text="Link zur Karte des Veranstaltungsorts. Er wird derzeit in der Admin-Tabelle und in Exporten verwendet." />
                </div>
                <flux:input wire:model="form.location_url" />
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
                <flux:button type="button" variant="ghost" size="sm" icon="information-circle" icon:variant="outline">Markdown-Hilfe</flux:button>
            </flux:modal.trigger>
        </div>

        <div class="grid gap-4">
            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Hero-Text</flux:label>
                    <x-admin.field-info label="Hero-Text" text="Erscheint direkt unter dem Anlass-Titel im Hero der Startseite. Inline-Markdown für Hervorhebungen und Links wird unterstützt." />
                </div>
                <flux:textarea rows="3" wire:model="form.content.hero.copy_md" />
                <flux:error name="form.content.hero.copy_md" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Homepage-Überschrift</flux:label>
                    <x-admin.field-info label="Homepage-Überschrift" text="Erscheint als Überschrift im Informationsbereich unterhalb des Hero-Bereichs auf der Startseite." />
                </div>
                <flux:input wire:model="form.content.home.about_heading" />
                <flux:error name="form.content.home.about_heading" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Homepage-Einleitung</flux:label>
                    <x-admin.field-info label="Homepage-Einleitung" text="Erscheint hervorgehoben direkt unter der Homepage-Überschrift. Markdown wird unterstützt." />
                </div>
                <flux:textarea rows="4" wire:model="form.content.home.about_intro_md" />
                <flux:error name="form.content.home.about_intro_md" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Homepage-Haupttext</flux:label>
                    <x-admin.field-info label="Homepage-Haupttext" text="Erscheint im Informationsbereich der Startseite vor den Hinweisen zur Teilnahme. Markdown wird unterstützt." />
                </div>
                <flux:textarea rows="6" wire:model="form.content.home.about_body_md" />
                <flux:error name="form.content.home.about_body_md" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Resultate-Überschrift</flux:label>
                    <x-admin.field-info label="Resultate-Überschrift" text="Erscheint als Seitentitel auf der öffentlichen Resultate-Seite. Formatierung wird dort als reiner Text ausgegeben." />
                </div>
                <flux:input wire:model="form.content.results.heading_md" />
                <flux:error name="form.content.results.heading_md" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>SEO-Beschreibung</flux:label>
                    <x-admin.field-info label="SEO-Beschreibung" text="Wird als Meta-Beschreibung für Suchmaschinen ausgegeben. Formatierung wird entfernt; nur Text bleibt bestehen." />
                </div>
                <flux:textarea rows="3" wire:model="form.content.seo.meta_description_md" />
                <flux:error name="form.content.seo.meta_description_md" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>OpenGraph-Beschreibung</flux:label>
                    <x-admin.field-info label="OpenGraph-Beschreibung" text="Wird als Beschreibung beim Teilen der Website verwendet. Formatierung wird entfernt; nur Text bleibt bestehen." />
                </div>
                <flux:textarea rows="3" wire:model="form.content.seo.og_description_md" />
                <flux:error name="form.content.seo.og_description_md" />
            </flux:field>

            <flux:field>
                <div class="flex items-center gap-1">
                    <flux:label>Zusatzinformation Spendenrechnung</flux:label>
                    <x-admin.field-info label="Zusatzinformation Spendenrechnung" text="Ist für anlassbezogene Rechnungstexte vorgesehen, wird derzeit aber noch nicht automatisch auf einer Rechnung ausgegeben." />
                </div>
                <flux:textarea rows="3" wire:model="form.content.invoice.additional_information" />
                <flux:error name="form.content.invoice.additional_information" />
            </flux:field>
        </div>
    </flux:card>

    <flux:modal name="donation-event-markdown-help" class="space-y-6 md:w-xl">
        <div>
            <flux:heading size="lg">Markdown-Syntax</flux:heading>
            <flux:subheading>Für Hero-Text, Homepage-Einleitung und Homepage-Haupttext.</flux:subheading>
        </div>

        <div class="grid gap-3 text-sm sm:grid-cols-2">
            <code>**fett**</code><span><strong>fett</strong></span>
            <code>*kursiv*</code><span><em>kursiv</em></span>
            <code>[Linktext](https://example.org)</code><span>Link</span>
            <code>- Listeneintrag</code><span>Aufzählung</span>
            <code>## Überschrift</code><span>Zwischenüberschrift</span>
            <code>Leerzeile</code><span>Neuer Absatz</span>
        </div>

        <flux:callout icon="shield-check">
            HTML wird entfernt. Unsichere Links werden nicht ausgegeben. Im Hero funktionieren nur Inline-Formatierungen wie fett, kursiv und Links.
        </flux:callout>
    </flux:modal>

    <div class="flex items-center gap-3">
        <flux:text wire:dirty class="text-sm text-accent">Ungespeicherte Änderungen</flux:text>
        <flux:spacer />
        <flux:button type="submit" variant="primary" wire:target="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">{{ $donationEvent === null ? 'Anlass erstellen' : 'Änderungen speichern' }}</span>
            <span wire:loading wire:target="save">Speichert...</span>
        </flux:button>
    </div>
</form>

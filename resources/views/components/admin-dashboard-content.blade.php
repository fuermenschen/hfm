<div>
    @php
        $routeParameters = ['anlass' => $selectedEventSlug ?? ''];
        $chartColors = [
            'text-red-600 dark:text-red-400',
            'text-sky-600 dark:text-sky-400',
            'text-emerald-600 dark:text-emerald-400',
            'text-violet-600 dark:text-violet-400',
            'text-amber-600 dark:text-amber-400',
            'text-pink-600 dark:text-pink-400',
        ];
        $chartMetrics = [
            ['title' => 'Sportler:innen-Registrierungen', 'data' => $chartData['registrations'], 'format' => null],
            ['title' => 'Spenden', 'data' => $chartData['donations'], 'format' => null],
            [
                'title' => 'Erwartete Spendensumme',
                'data' => $chartData['expectedAmount'],
                'format' => ['style' => 'currency', 'currency' => 'CHF'],
                'axisFormat' => [],
                'locale' => 'de-CH',
                'compactYAxis' => true,
            ],
        ];
    @endphp
    <x-expandable-card class="mt-9" :max-height="360" expand-mode="icon-animated">
        <flux:heading size="xl">Entwicklung rund um den Anlass</flux:heading>
        <flux:text class="mt-1">
            Zeitraum von 60 Tagen vor bis 14 Tage nach dem Anlass. Spendenschätzungen werden mit aktuell hinterlegten
            Werten nach Erfassungsdatum dargestellt, nicht als historische Momentaufnahmen.
        </flux:text>

        @if ($chartEvents === [] || $chartData['registrations'] === [])
            <flux:text class="mt-5">Für diesen Zeitraum sind noch keine Daten vorhanden.</flux:text>
        @elseif (count($chartData['registrations']) === 1)
            <flux:text class="mt-5">Für eine Entwicklung werden Daten von mindestens zwei Tagen benötigt.</flux:text>
        @else
            <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2" aria-label="Anlässe">
                @foreach ($chartEvents as $chartEvent)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="size-2 rounded-full {{ $chartColors[$chartEvent['colorIndex']] }} bg-current"></span>
                        <span>{{ $chartEvent['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-7 space-y-10">
                @foreach ($chartMetrics as $metric)
                    <section>
                        <flux:heading size="lg">{{ $metric['title'] }}</flux:heading>
                        <flux:chart
                            :value="$metric['data']"
                            class="mt-4"
                            :locale="$metric['locale'] ?? null"
                            :data-compact-y-axis="$metric['compactYAxis'] ?? null"
                        >
                            <flux:chart.viewport class="h-56 sm:h-64">
                                <flux:chart.svg>
                                    @foreach ($chartEvents as $chartEvent)
                                        <flux:chart.line
                                            :field="$chartEvent['field']"
                                            :class="$chartColors[$chartEvent['colorIndex']]"
                                            curve="none"
                                        />
                                    @endforeach

                                    @foreach ($chartTodayMarkers as $marker)
                                        @php($position = (($marker['day'] - min($chartTickValues)) / (max($chartTickValues) - min($chartTickValues))) * 100)
                                        @php($tickIndex = array_search($marker['day'], $chartTickValues, true))
                                        <line
                                            data-today-marker="{{ $tickIndex }}"
                                            x1="{{ $position }}%"
                                            x2="{{ $position }}%"
                                            y1="0"
                                            y2="100%"
                                            class="text-zinc-500 dark:text-zinc-300"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-dasharray="4 4"
                                            pointer-events="none"
                                        />
                                    @endforeach

                                    <flux:chart.axis
                                        axis="x"
                                        field="day"
                                        scale="linear"
                                        :tick-values="$chartTickValues"
                                        tick-prefix="Tag&nbsp;"
                                    >
                                        <flux:chart.axis.grid />
                                        <flux:chart.axis.tick class="text-[10px] sm:text-xs" />
                                        <flux:chart.axis.line />
                                    </flux:chart.axis>

                                    <flux:chart.axis axis="y" :format="$metric['axisFormat'] ?? $metric['format']">
                                        <flux:chart.axis.grid />
                                        <flux:chart.axis.tick />
                                    </flux:chart.axis>

                                    <flux:chart.cursor />
                                </flux:chart.svg>

                                <flux:chart.tooltip>
                                    <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 p-2 text-xs font-medium text-zinc-800 dark:border-zinc-500 dark:bg-zinc-600 dark:text-zinc-100">
                                        Tag&nbsp;<slot field="day"></slot>
                                    </div>
                                    @foreach ($chartEvents as $chartEvent)
                                        <flux:chart.tooltip.value
                                            :field="$chartEvent['field']"
                                            :label="$chartEvent['slug']"
                                            :format="$metric['format']"
                                        />
                                    @endforeach
                                </flux:chart.tooltip>
                            </flux:chart.viewport>
                        </flux:chart>
                    </section>
                @endforeach
            </div>

            <flux:text class="mt-7 text-sm">
                Tag 0 ist Tag des Anlasses. Die erwartete Spendensumme basiert auf den geschätzten Runden.
                @if ($chartTodayMarkers !== [])
                    Heute:
                    @foreach ($chartTodayMarkers as $marker)
                        {{ $marker['slug'] }} Tag {{ $marker['day'] }}{{ $loop->last ? '.' : ',' }}
                    @endforeach
                    Die vertikale gestrichelte Linie markiert den heutigen Stand.
                @endif
            </flux:text>
        @endif
    </x-expandable-card>

    <!-- Athlete -->
    <x-stats title="Sportler:innen">
        <x-admin.stat-card
            title="Registriert"
            :value="$athleteCount"
            route="admin.athletes.index"
            :route-parameters="$routeParameters"
        />
        <x-admin.stat-card
            title="Verifiziert"
            :value="$verifiedAthleteCount"
            route="admin.athletes.index"
            :route-parameters="$routeParameters"
        />
        <x-admin.stat-card
            title="Geschätzte Runden Ø"
            tooltip="Durchschnitt der bei der Anmeldung geschätzten Runden. Tatsächlich absolvierte Runden werden hier nicht verwendet."
            :value="round($meanNumberOfRounds, 0)"
            route="admin.athletes.index"
            :route-parameters="$routeParameters"
        />
        <x-admin.stat-card
            title="Durchschn. Spenden"
            :value="round($meanNumberOfDonations, 0)"
            tooltip="Anzahl Spendenzusagen geteilt durch Anzahl Sportler:innen. Zeigt die durchschnittliche Zahl der Zusagen, nicht deren Geldbetrag."
            route="admin.athletes.index"
            :route-parameters="$routeParameters"
        />
    </x-stats>

    <x-stats title="Gruppen">
        <flux:card class="h-full">
            <dt><flux:text class="truncate">Registriert</flux:text></dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">{{ $eventGroupCount }}</dd>
        </flux:card>
    </x-stats>

    <!-- Donation -->
    <x-stats title="Spenden">
        <x-admin.stat-card
            title="Registriert"
            :value="$donationCount"
            route="admin.donations.index"
            :route-parameters="$routeParameters"
        />
        <x-admin.stat-card
            title="Bestätigt"
            :value="$verifiedDonationCount"
            tooltip="Bestätigte Spendenzusagen. Die Bestätigung betrifft die Registrierung, nicht den Zahlungseingang."
            route="admin.donations.index"
            :route-parameters="$routeParameters"
        />
        <x-admin.stat-card
            title="Durchschn. Betrag pro Runde"
            tooltip="Einfacher Durchschnitt der zugesagten Beträge pro Runde. Mindest- und Höchstbeträge sowie tatsächliche Zahlungen sind nicht berücksichtigt."
            :value="'Fr. '.round($meanDonationAmount, 2)"
            route="admin.donations.index"
            :route-parameters="$routeParameters"
        />
    </x-stats>

    <!-- Estimated Amounts -->
    <x-stats title="Spenden (geschätzt)">
        <x-admin.stat-card
            title="Erwartete Spenden"
            tooltip="Geschätzte Spendensumme aus erwarteten Runden und Spendenzusagen, einschliesslich Mindest- und Höchstbeträgen."
            :value="'Fr. '.round($expectedDonationAmount, 2)"
            route="admin.donations.index"
            :route-parameters="$routeParameters"
        />
        @foreach ($partners as $partner)
            <x-admin.stat-card
                title="{{ $partner->name }}"
                :value="'Fr. '.round($estimatedAmounts[$partner->id] ?? 0, 2)"
                route="admin.donations.index"
                :route-parameters="$routeParameters"
            />
        @endforeach
    </x-stats>

    <!-- Actual Amounts -->
    <x-stats title="Spenden (berechnet)">
        <x-admin.stat-card
            title="Berechnete Spenden"
            tooltip="Aus den erfassten Runden berechnete Spendensumme, unter Berücksichtigung zugesagter Mindest- und Höchstbeträge. Kein Zahlungseingang."
            :value="'Fr. '.round($actualTotalAmount, 2)"
            route="admin.donations.index"
            :route-parameters="$routeParameters"
        />
        @foreach ($partners as $partner)
            <x-admin.stat-card
                title="{{ $partner->name }}"
                :value="'Fr. '.round($actualAmounts[$partner->id] ?? 0, 2)"
                route="admin.donations.index"
                :route-parameters="$routeParameters"
            />
        @endforeach
    </x-stats>

    <!-- Donor -->
    <x-stats title="Spender:innen">
        <x-admin.stat-card
            title="Registriert"
            :value="$donorCount"
            route="admin.donors.index"
            :route-parameters="$routeParameters"
        />
        <x-admin.stat-card
            title="Durchschn. Spenden"
            :value="round($meanNumberOfDonationsDonor, 1)"
            tooltip="Anzahl Spendenzusagen geteilt durch Anzahl Spender:innen. Eine Person kann mehrere Spendenzusagen hinterlegt haben."
            route="admin.donors.index"
            :route-parameters="$routeParameters"
        />
    </x-stats>

    <x-stats title="Spenden (bezahlt)">
        <x-admin.stat-card
            title="Verifizierte Zahlungseingänge"
            tooltip="Verifiziertes Geld zu vollständig abgeglichenen, in Webling als bezahlt geführten Rechnungen. Einschliesslich Mehrzahlungen und mehrerer Teilzahlungen. Zahlungen zu noch offenen oder unklaren Rechnungen sind ausgeschlossen."
            :value="'Fr. '.number_format($paymentSummary['received_cents'] / 100, 2, '.', chr(39))"
            route="admin.donors.index"
            :route-parameters="$routeParameters"
        />
        @foreach ($paymentSummary['partners'] as $paidPartner)
            <x-admin.stat-card
                :title="$paidPartner['name']"
                tooltip="Rechnerischer Anteil der verifizierten Zahlungseingänge nach gespeicherter HFM-Rechnungsaufteilung; fehlt diese, anhand aktueller Spenden. Einschliesslich Mehrzahlungen. Keine Auszahlung an die Partnerorganisation."
                :value="'Fr. '.number_format($paidPartner['amount_cents'] / 100, 2, '.', chr(39))"
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
        @endforeach
        @if ($paymentSummary['unallocated_cents'] > 0)
            <x-admin.stat-card
                title="Unklare Partnerzuordnung"
                tooltip="Bereits verifiziertes Geld, dessen Partneranteil nicht eindeutig zugeordnet werden kann. Im gesamten verifizierten Zahlungseingang enthalten."
                :value="'Fr. '.number_format($paymentSummary['unallocated_cents'] / 100, 2, '.', chr(39))"
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
        @endif
    </x-stats>
    <flux:text class="mt-3">
        Enthalten sind verifizierte Zahlungseingänge abgeschlossener Rechnungen, einschliesslich Mehrzahlungen. Mehrere
        Teilzahlungen werden berücksichtigt, sobald die Rechnung vollständig abgeglichen ist.
    </flux:text>

    <x-stats title="Rechnungen und Zahlungsabgleich">
        @foreach ([
            'Erstellt (gesamt)' => [
                'value' => $paymentSummary['created_total'],
                'tooltip' => 'Rechnungen mit bestehender Webling-Verknüpfung. In Webling gelöschte Rechnungen sind nicht enthalten.',
            ],
            'Gesendet (gesamt)' => [
                'value' => $paymentSummary['sent_total'],
                'tooltip' => 'Bereits gesendete Rechnungen mit bestehender Webling-Verknüpfung, unabhängig vom aktuellen Zahlungsstatus. In Webling gelöschte Rechnungen sind ausgeschlossen.',
            ],
            'Zahlung verifiziert' => [
                'value' => $paymentSummary['verified_receipts'],
                'tooltip' => 'Anzahl vollständig abgeglichener Rechnungen mit verifiziertem Zahlungseingang. Mehrere Zahlungen für dieselbe Rechnung zählen einmal.',
            ],
            'Zahlung unklar' => [
                'value' => $paymentSummary['unclear_payments'],
                'tooltip' => 'Anzahl Rechnungen, deren Zahlungsnachweis nicht automatisch verifiziert werden konnte. Bedeutet nicht, dass Zahlung oder zahlende Person unbekannt sind.',
            ],
            'Noch nie abgeglichen' => [
                'value' => $paymentSummary['never_synced'],
                'tooltip' => 'Vorhandene Webling-Rechnungen ohne gespeicherten erfolgreichen Abgleich. Fehlgeschlagene Versuche ändern diesen Wert nicht.',
            ],
        ] as $label => $card)
            <x-admin.stat-card
                :title="$label"
                :value="$card['value']"
                :tooltip="$card['tooltip']"
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
        @endforeach
        @foreach (\App\Enums\DonorInvoiceStatus::cases() as $status)
            @php($statusTooltip = match ($status) {
                \App\Enums\DonorInvoiceStatus::Paid => 'Rechnungen mit Webling-Status «bezahlt». Dieser Status allein bestätigt keinen verifizierten Zahlungseingang und kann Teilabschreibungen enthalten.',
                \App\Enums\DonorInvoiceStatus::Writeoff => 'Rechnungen mit Webling-Status «abgeschrieben». Teilabschreibungen auf ansonsten bezahlten Rechnungen sind hier nicht mitgezählt.',
                \App\Enums\DonorInvoiceStatus::RemoteDeleted => 'Rechnungen, deren Löschung in Webling beim Abgleich festgestellt wurde. Keine gelöschten Spender:innen.',
                default => null,
            })
            <x-admin.stat-card
                :title="$status->label()"
                :tooltip="$statusTooltip"
                :value="$paymentSummary['counts'][$status->value]"
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
        @endforeach
        @foreach ([
            'Offener Rechnungsbetrag (Webling)' => [
                'value' => $paymentSummary['outstanding_cents'],
                'tooltip' => 'Summe der zuletzt aus Webling übernommenen offenen Rechnungsbeträge. Nachträgliche Änderungen in Webling sind erst nach erneutem Abgleich sichtbar.',
            ],
            'Mehrzahlungen (verifiziert)' => [
                'value' => $paymentSummary['excess_cents'],
                'tooltip' => 'Verifizierter Zahlungseingang über dem ursprünglichen HFM-Rechnungsbetrag. Ein nachträglich erhöhter Webling-Rechnungsbetrag verändert diese Berechnungsgrundlage nicht.',
            ],
            'Abgeschrieben (Webling)' => [
                'value' => $paymentSummary['writeoff_cents'],
                'tooltip' => 'Summe der aus Webling übernommenen Abschreibungsbeträge. Enthält auch Teilabschreibungen auf bezahlten Rechnungen; kein Zahlungseingang.',
            ],
        ] as $label => $card)
            <x-admin.stat-card
                :title="$label"
                :value="'Fr. '.number_format($card['value'] / 100, 2, '.', chr(39))"
                :tooltip="$card['tooltip']"
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
        @endforeach
    </x-stats>
    <flux:text class="mt-3">
        Status aus lokalem Rechnungsstand und letztem Webling-Abgleich. Überfälligkeit wird anhand des heutigen Datums
        bestimmt. Die Statuskarten zeigen jeweils eine ausschliessliche Kategorie; die Gesamtzahlen für erstellte und
        gesendete Rechnungen überschneiden sich damit.
        @if ($paymentSummary['breakdown_missing'] > 0)
            Bei {{ $paymentSummary['breakdown_missing'] }} Rechnung(en) fehlt noch die Aufschlüsselung;
                Mehrzahlungen und Abschreibungen zeigen nur bereits ermittelte Beträge.
        @endif
    </flux:text>

    <!-- Recent Activities -->
    <x-admin.activity-list title="Letzte Aktivitäten" :activities="$mostRecentActivities" />
</div>

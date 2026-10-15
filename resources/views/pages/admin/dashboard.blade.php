@component('layouts.admin', ['title' => $greeting.Auth::user()->name])
    @section('content')
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

        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex justify-end">
            <flux:field class="w-full sm:w-80">
                <flux:label>Anlass</flux:label>
                <flux:select name="anlass" onchange="this.form.submit()">
                    <option value="" @selected($selectedEventSlug === null)>Alle Anlässe</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->slug }}" @selected($selectedEventSlug === $event->slug)>
                            {{ $event->title }} ({{ $event->slug }}){{ $event->is_published ? '' : ' - NICHT VERÖFFENTLICHT' }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>
        </form>

        <x-expandable-card class="mt-9" :max-height="360" expand-mode="icon-animated">
            <flux:heading size="xl">Entwicklung bis zum Anlass</flux:heading>
            <flux:text class="mt-1"
                >Kumulative Registrierungen, Spenden und erwartete Spendensumme relativ zum Start des
                Anlasses.</flux:text>

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
                title="Durchschn. Runden"
                :value="round($meanNumberOfRounds, 0)"
                route="admin.athletes.index"
                :route-parameters="$routeParameters"
            />
            <x-admin.stat-card
                title="Durchschn. Spenden"
                :value="round($meanNumberOfDonations, 0)"
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
                title="Verifiziert"
                :value="$verifiedDonationCount"
                route="admin.donations.index"
                :route-parameters="$routeParameters"
            />
            <x-admin.stat-card
                title="Durchschn. Betrag pro Runde"
                :value="'Fr. '.round($meanDonationAmount, 2)"
                route="admin.donations.index"
                :route-parameters="$routeParameters"
            />
        </x-stats>

        <!-- Estimated Amounts -->
        <x-stats title="Spenden (geschätzt)">
            <x-admin.stat-card
                title="Erwartete Spenden"
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
        <x-stats title="Spenden (tatsächlich)">
            <x-admin.stat-card
                title="Tatsächliche Spenden"
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
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
        </x-stats>

        <x-stats title="Spenden (bezahlt)">
            <x-admin.stat-card
                title="Verifizierte Zahlungseingänge"
                :value="'Fr. '.number_format($paymentSummary['received_cents'] / 100, 2, '.', chr(39))"
                route="admin.donors.index"
                :route-parameters="$routeParameters"
            />
            @foreach ($paymentSummary['partners'] as $paidPartner)
                <x-admin.stat-card
                    :title="$paidPartner['name']"
                    :value="'Fr. '.number_format($paidPartner['amount_cents'] / 100, 2, '.', chr(39))"
                    route="admin.donors.index"
                    :route-parameters="$routeParameters"
                />
            @endforeach
            @if ($paymentSummary['unallocated_cents'] > 0)
                <x-admin.stat-card
                    title="Noch nicht zugeordnet"
                    :value="'Fr. '.number_format($paymentSummary['unallocated_cents'] / 100, 2, '.', chr(39))"
                    route="admin.donors.index"
                    :route-parameters="$routeParameters"
                />
            @endif
        </x-stats>
        <flux:text class="mt-3">
            Nur verifizierte Zahlungseingänge abgeschlossener Rechnungen, inklusive Mehrzahlungen. Teilzahlungen und
            unklare Zahlungseingänge sind nicht enthalten. Die Verteilung folgt den Partneranteilen der jeweiligen
            Rechnung, inklusive Mehrzahlungen.
        </flux:text>

        <x-stats title="Rechnungen und Zahlungsabgleich">
            @foreach ([
                'Erstellt (gesamt)' => $paymentSummary['created_total'],
                'Gesendet (gesamt)' => $paymentSummary['sent_total'],
                'Verifizierte Zahlungseingänge' => $paymentSummary['verified_receipts'],
                'Unklare Zahlungseingänge' => $paymentSummary['unclear_payments'],
                'Noch nie abgeglichen' => $paymentSummary['never_synced'],
            ] as $label => $count)
                <x-admin.stat-card
                    :title="$label"
                    :value="$count"
                    route="admin.donors.index"
                    :route-parameters="$routeParameters"
                />
            @endforeach
            @foreach (\App\Enums\DonorInvoiceStatus::cases() as $status)
                <x-admin.stat-card
                    :title="$status->label()"
                    :value="$paymentSummary['counts'][$status->value]"
                    route="admin.donors.index"
                    :route-parameters="$routeParameters"
                />
            @endforeach
            @foreach ([
                'Offener Rechnungsbetrag (Webling)' => $paymentSummary['outstanding_cents'],
                'Mehrzahlungen (verifiziert)' => $paymentSummary['excess_cents'],
                'Abgeschrieben (Webling)' => $paymentSummary['writeoff_cents'],
            ] as $label => $amount)
                <x-admin.stat-card
                    :title="$label"
                    :value="'Fr. '.number_format($amount / 100, 2, '.', chr(39))"
                    route="admin.donors.index"
                    :route-parameters="$routeParameters"
                />
            @endforeach
        </x-stats>
        <flux:text class="mt-3">
            Rechnungsstatus gemäss letztem Webling-Abgleich. «Bezahlt» ist nicht gleichbedeutend mit verifiziertem
            Zahlungseingang. Gesendete Rechnungen zählen unabhängig vom aktuellen Zahlungsstatus.
            @if ($paymentSummary['breakdown_missing'] > 0)
                Bei {{ $paymentSummary['breakdown_missing'] }} Rechnung(en) fehlt noch die Aufschlüsselung;
                Mehrzahlungen und Abschreibungen zeigen nur bereits ermittelte Beträge.
            @endif
        </flux:text>

        <!-- Recent Activities -->
        <x-admin.activity-list title="Letzte Aktivitäten" :activities="$mostRecentActivities" />

    @endsection

@endcomponent

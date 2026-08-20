@component('layouts.admin', ['title' => $greeting . Auth::user()->name])

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
                ['title' => 'Erwartete Spendensumme', 'data' => $chartData['expectedAmount'], 'format' => ['style' => 'currency', 'currency' => 'CHF']],
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

        <flux:card class="mt-9">
            <flux:heading size="xl">Entwicklung bis zum Anlass</flux:heading>
            <flux:text class="mt-1">Kumulative Registrierungen, Spenden und erwartete Spendensumme relativ zum Start des Anlasses.</flux:text>

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
                            <flux:chart :value="$metric['data']" class="mt-4">
                                <flux:chart.viewport class="h-56 sm:h-64">
                                    <flux:chart.svg>
                                        @foreach ($chartEvents as $chartEvent)
                                            <flux:chart.line :field="$chartEvent['field']" :class="$chartColors[$chartEvent['colorIndex']]" curve="none" />
                                        @endforeach

                                        <flux:chart.axis axis="x" field="day" scale="linear" :tick-values="$chartTickValues" tick-prefix="Tag&nbsp;">
                                            <flux:chart.axis.grid />
                                            <flux:chart.axis.tick class="text-[10px] sm:text-xs" />
                                            <flux:chart.axis.line />
                                        </flux:chart.axis>

                                        <flux:chart.axis axis="y" :format="$metric['format']">
                                            <flux:chart.axis.grid />
                                            <flux:chart.axis.tick />
                                        </flux:chart.axis>

                                        <flux:chart.cursor />
                                    </flux:chart.svg>
                                </flux:chart.viewport>

                                <flux:chart.tooltip>
                                    <flux:chart.tooltip.heading field="day" />
                                    @foreach ($chartEvents as $chartEvent)
                                        <flux:chart.tooltip.value :field="$chartEvent['field']" :label="$chartEvent['label']" :format="$metric['format']" />
                                    @endforeach
                                </flux:chart.tooltip>
                            </flux:chart>
                        </section>
                    @endforeach
                </div>

                <flux:text class="mt-7 text-sm">Tag 0 ist Tag des Anlassess. Die erwartete Spendensumme basiert auf den geschätzten Runden.</flux:text>
            @endif
        </flux:card>

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

        <!-- Recent Activities -->
        <x-admin.activity-list title="Letzte Aktivitäten" :activities="$mostRecentActivities" />

    @endsection

@endcomponent

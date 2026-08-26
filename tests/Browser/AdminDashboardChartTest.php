<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\SportType;
use App\Models\User;
use App\Settings\EventSettings;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;

it('keeps dashboard today markers aligned with each chart plot', function (): void {
    Carbon::setTestNow('2026-09-02 12:00:00');
    $event = DonationEvent::factory()->year(2026)->create();
    $sportType = SportType::query()->create(['name' => 'Run']);
    $registration = AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-09-01 12:00:00',
        'rounds_estimated' => 20,
    ]);

    Donation::factory()
        ->forAthleteRegistration($registration)
        ->create([
            'amount_per_round' => 1000,
            'amount_max' => 100000,
            'amount_min' => 1000,
            'created_at' => '2026-09-01 13:00:00',
            'verified' => true,
        ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    actingAs(User::factory()->create());

    $page = visit(route('admin.dashboard'))->assertSee('Heute:')->assertNoJavaScriptErrors();

    $alignmentScript = <<<'JS'
        Array.from(document.querySelectorAll('ui-chart')).every((chart) => {
            const svg = chart.querySelector('svg');
            const gridLines = Array.from(svg.querySelectorAll('[data-grid-line][data-axis="x"]'));

            return Array.from(chart.querySelectorAll('[data-today-marker]')).every((marker) => {
                const gridLine = gridLines[Number.parseInt(marker.dataset.todayMarker, 10)];

                return gridLine
                    && marker.getAttribute('x1') === gridLine.getAttribute('x1')
                    && marker.getAttribute('x2') === gridLine.getAttribute('x2')
                    && marker.getAttribute('y1') === gridLine.getAttribute('y1')
                    && marker.getAttribute('y2') === gridLine.getAttribute('y2');
            });
        })
        JS;

    $markersAreAligned = static function () use ($alignmentScript, $page): void {
        expect($page->script($alignmentScript))->toBeTrue();
    };

    expect($page->script(<<<'JS'
        (() => {
            const labels = Array.from(document.querySelectorAll('ui-chart'))
                .at(-1)
                .querySelectorAll('[data-tick-label][data-axis="y"] text');

            return document.querySelectorAll('ui-chart')[2].getAttribute('locale') === 'de-CH'
                && labels.length > 1
                && Array.from(labels).every((label) => {
                    const text = label.textContent.trim().toLowerCase();

                    return text === '0' || /^\d+k$/.test(text);
                });
        })()
        JS))->toBeTrue();

    $markersAreAligned();
    $page->resize(640, 900)->wait(0.2);
    $markersAreAligned();

    Carbon::setTestNow();
});

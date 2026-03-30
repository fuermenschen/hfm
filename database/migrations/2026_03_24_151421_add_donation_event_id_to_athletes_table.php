<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('athletes', function (Blueprint $table): void {
                $table->foreignId('donation_event_id')
                    ->nullable()
                    ->after('partner_id')
                    ->constrained('donation_events');
            });
        });

        $this->seedCanonicalDonationEvents();

        $eventIds = DB::table('donation_events')
            ->whereIn('slug', ['2025', '2026'])
            ->pluck('id', 'slug')
            ->all();

        $event2025Id = (int) ($eventIds['2025'] ?? 0);
        $event2026Id = (int) ($eventIds['2026'] ?? 0);

        if ($event2025Id <= 0 || $event2026Id <= 0) {
            throw new RuntimeException('Could not resolve canonical donation events for 2025 and 2026.');
        }

        DB::table('athletes')
            ->where('created_at', '>=', '2025-01-01 00:00:00')
            ->where('created_at', '<', '2026-01-01 00:00:00')
            ->update(['donation_event_id' => $event2025Id]);

        DB::table('athletes')
            ->where('created_at', '>=', '2026-01-01 00:00:00')
            ->where('created_at', '<', '2027-01-01 00:00:00')
            ->update(['donation_event_id' => $event2026Id]);

        $this->exportAndDeleteUnassignedAthletes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            Schema::table('athletes', function (Blueprint $table): void {
                $table->dropForeign(['donation_event_id']);
                $table->dropColumn('donation_event_id');
            });
        });
    }

    protected function seedCanonicalDonationEvents(): void
    {
        DB::table('donation_events')->upsert(
            [
                [
                    'slug' => '2025',
                    'title' => 'Höhenmeter für Menschen',
                    'timezone' => 'Europe/Zurich',
                    'starts_at' => '2025-09-13 11:00:00',
                    'ends_at' => '2025-09-13 16:00:00',
                    'registration_opens_at' => '2025-01-31 23:00:00',
                    'athlete_registration_closes_at' => '2025-09-13 14:00:00',
                    'donor_registration_closes_at' => '2025-09-21 21:59:59',
                    'location_name' => 'Brühlgut Stiftung',
                    'location_street' => 'Brühlbergstrasse 6',
                    'location_postal_code' => '8400',
                    'location_city' => 'Winterthur',
                    'location_url' => 'https://s.geo.admin.ch/yat5fpx761jk',
                    'is_published' => true,
                    'content' => json_encode($this->eventContent(2025, '13. September 2025', '13 Uhr bis 18 Uhr'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
                [
                    'slug' => '2026',
                    'title' => 'Höhenmeter für Menschen',
                    'timezone' => 'Europe/Zurich',
                    'starts_at' => '2026-09-12 11:00:00',
                    'ends_at' => '2026-09-12 14:00:00',
                    'registration_opens_at' => '2026-09-12 11:00:00',
                    'athlete_registration_closes_at' => '2026-09-12 11:00:00',
                    'donor_registration_closes_at' => '2026-09-20 21:59:59',
                    'location_name' => 'Brühlgut Stiftung',
                    'location_street' => 'Brühlbergstrasse 6',
                    'location_postal_code' => '8400',
                    'location_city' => 'Winterthur',
                    'location_url' => 'https://s.geo.admin.ch/yat5fpx761jk',
                    'is_published' => true,
                    'content' => json_encode($this->eventContent(2026, '12. September 2026', '13 Uhr bis 16 Uhr'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
            ['slug'],
            [
                'title',
                'timezone',
                'starts_at',
                'ends_at',
                'registration_opens_at',
                'athlete_registration_closes_at',
                'donor_registration_closes_at',
                'location_name',
                'location_street',
                'location_postal_code',
                'location_city',
                'location_url',
                'is_published',
                'content',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function eventContent(int $year, string $eventDateLabel, string $eventTimeWindow): array
    {
        return [
            'hero' => [
                'copy_md' => 'Ein Spendenlauf für Winterthur. Wir rennen, fahren, rollen - für lokale Benefizpartner:innen. Bist auch du am Start?',
            ],
            'home' => [
                'about_heading' => 'Um was geht es?',
                'about_intro_md' => 'Höhenmeter für Menschen ist ein Spendenlauf in Winterthur. Es werden Spenden für lokale Benefizpartner:innen gesammelt.',
                'about_body_md' => 'Der Spendenlauf ist ähnlich organisiert wie ein klassischer «Sponsorenlauf». Nur wird nicht für einen Verein gesammelt, sondern für Kinder psychisch kranker Eltern, für Menschen mit Beeinträchtigung und für Menschen in schwierigen Lebenssituationen.',
            ],
            'results' => [
                'heading_md' => "Resultate {$year}",
            ],
            'faq' => [
                'general_event_md' => "Der Spendenlauf findet am **Samstag, {$eventDateLabel} in Winterthur** statt. Der Anlass dauert von **{$eventTimeWindow}**. Start und Ziel des Rundkurses sind bei der Brühlgut Stiftung (Brühlbergstrasse 6).",
            ],
            'seo' => [
                'meta_description_md' => "Höhenmeter für Menschen: Ein Spendenlauf in Winterthur am {$eventDateLabel}. Mit den Spenden werden Winterthurer Benefizpartner:innen unterstützt.",
                'og_description_md' => "Ein Spendenlauf in Winterthur für Winterthur am {$eventDateLabel}.",
            ],
            'invoice' => [
                'additional_information' => "Spendenrechnung Höhenmeter für Menschen Winterthur {$year}",
            ],
        ];
    }

    protected function exportAndDeleteUnassignedAthletes(): void
    {
        $unassignedAthleteIds = DB::table('athletes')
            ->whereNull('donation_event_id')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($unassignedAthleteIds === []) {
            return;
        }

        $athletes = DB::table('athletes')
            ->whereIn('id', $unassignedAthleteIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $donations = DB::table('donations')
            ->whereIn('athlete_id', $unassignedAthleteIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $donorIds = array_values(array_unique(array_map(
            static fn (array $donation): int => (int) $donation['donor_id'],
            $donations,
        )));

        $donors = $donorIds === []
            ? []
            : DB::table('donors')
                ->whereIn('id', $donorIds)
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();

        Storage::disk('local')->makeDirectory('exports');

        $timestamp = now()->format('Ymd_His');
        $relativePath = "exports/deleted-unassigned-athletes_{$timestamp}.json";

        Storage::disk('local')->put(
            $relativePath,
            json_encode([
                'generated_at' => now()->toIso8601String(),
                'reason' => 'Athletes outside 2025 and 2026 were exported and deleted during donation_event_id backfill.',
                'athlete_ids' => $unassignedAthleteIds,
                'athletes' => $athletes,
                'donations' => $donations,
                'donors' => $donors,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        Log::warning('Deleted unassigned athletes during donation event migration.', [
            'export_path' => 'storage/app/'.$relativePath,
            'deleted_athletes_count' => count($unassignedAthleteIds),
            'related_donations_count' => count($donations),
            'related_donors_count' => count($donors),
        ]);

        DB::table('athletes')->whereIn('id', $unassignedAthleteIds)->delete();
    }
};

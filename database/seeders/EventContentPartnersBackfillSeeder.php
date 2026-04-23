<?php

namespace Database\Seeders;

use App\Models\DonationEvent;
use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventContentPartnersBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $eventIds = DonationEvent::query()
            ->whereIn('slug', ['2025', '2026'])
            ->pluck('id', 'slug')
            ->all();

        if (! isset($eventIds['2025']) || ! isset($eventIds['2026'])) {
            return;
        }

        $rows = [
            [
                'donation_event_id' => (int) $eventIds['2025'],
                'name' => 'Brühlgut Stiftung',
                'logo_light_filename' => 'bruehlgut_light.svg',
                'logo_dark_filename' => 'bruehlgut_dark.svg',
                'beneficiary_blurb' => 'Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung.',
                'url' => 'https://www.xn--brhlgut-o2a.ch/',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $eventIds['2025'],
                'name' => 'Institut Kinderseele Schweiz',
                'logo_light_filename' => 'iks_light.svg',
                'logo_dark_filename' => 'iks_dark.svg',
                'beneficiary_blurb' => 'Das Institut Kinderseele Schweiz unterstützt Kinder psychisch erkrankter Eltern.',
                'url' => 'https://kinderseele.ch',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $eventIds['2025'],
                'name' => 'Tel. 143 - Die Dargebotene Hand',
                'logo_light_filename' => '143_light.svg',
                'logo_dark_filename' => '143_dark.svg',
                'beneficiary_blurb' => 'Die Dargebotene Hand bietet Menschen in schwierigen Lebenslagen rund um die Uhr Unterstützung.',
                'url' => 'https://143.ch',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'donation_event_id' => (int) $eventIds['2026'],
                'name' => 'Brühlgut Stiftung',
                'logo_light_filename' => 'bruehlgut_light.svg',
                'logo_dark_filename' => 'bruehlgut_dark.svg',
                'beneficiary_blurb' => 'Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung.',
                'url' => 'https://www.xn--brhlgut-o2a.ch/',
                'sort_order' => 1,
                'is_published' => true,
            ],
        ];

        foreach ($rows as $row) {
            $partner = Partner::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'logo_light_filename' => $row['logo_light_filename'],
                    'logo_dark_filename' => $row['logo_dark_filename'],
                    'beneficiary_blurb' => $row['beneficiary_blurb'],
                    'url' => $row['url'],
                ],
            );

            DB::table('donation_event_partner')->upsert(
                [
                    [
                        'donation_event_id' => $row['donation_event_id'],
                        'partner_id' => $partner->id,
                        'sort_order' => $row['sort_order'],
                        'is_published' => $row['is_published'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                ],
                ['donation_event_id', 'partner_id'],
                ['sort_order', 'is_published', 'updated_at'],
            );
        }
    }
}

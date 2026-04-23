<?php

namespace Database\Factories;

use App\Models\DonationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationEvent>
 */
class DonationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) fake()->unique()->numberBetween(2030, 2099);

        return [
            'slug' => (string) $year,
            'title' => 'Hoehenmeter fuer Menschen',
            'timezone' => 'Europe/Zurich',
            'starts_at' => "{$year}-09-12 13:00:00",
            'ends_at' => "{$year}-09-12 18:00:00",
            'registration_opens_at' => "{$year}-02-01 00:00:00",
            'athlete_registration_closes_at' => "{$year}-09-12 13:00:00",
            'donor_registration_closes_at' => "{$year}-09-20 23:59:59",
            'location_name' => 'Bruehlgut Stiftung',
            'location_street' => 'Bruehlbergstrasse 6',
            'location_postal_code' => '8400',
            'location_city' => 'Winterthur',
            'location_url' => 'https://s.geo.admin.ch/yat5fpx761jk',
            'is_published' => true,
            'content' => [
                'hero' => [
                    'copy_md' => 'Ein Spendenlauf für Winterthur. Wir rennen, fahren, rollen - für lokale Benefizpartner:innen. Bist auch du am Start?',
                ],
            ],
        ];
    }
}

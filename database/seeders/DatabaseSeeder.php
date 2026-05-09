<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // create users
        User::query()->create([
            'name' => 'Simon',
            'email' => 'simon.moser@mailbox.org',
        ]);
        User::query()->create([
            'name' => 'Kai',
            'email' => 'kaifrehner@gmail.com',
        ]);

        User::query()->create([
            'name' => 'Felix',
            'email' => 'felix.moser@mailbox.org',
        ]);

        // create sport types
        SportType::query()->create([
            'name' => 'Rennen',
        ]);

        SportType::query()->create([
            'name' => 'Velofahren',
        ]);

        SportType::query()->create([
            'name' => 'Inlineskaten',
        ]);

        SportType::query()->create([
            'name' => 'Rollstuhl',
        ]);

        SportType::query()->create([
            'name' => 'Andere (bitte spezifizieren)',
        ]);

        DonationEvent::factory(2)->create();

        Partner::query()->create(['name' => 'Partner A']);
        Partner::query()->create(['name' => 'Partner B']);
        Partner::query()->create(['name' => 'Partner C']);
        Partner::query()->create(['name' => 'Partner D']);

        // create example data
        if (config('app.env') === 'local') {
            Athlete::factory(10)->create();
            Athlete::factory(20)->verified()->create();
            Donor::factory(150)->create();
            Donation::factory(250)->create();
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Athlete;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // create athletes (example data)
        Athlete::factory(10)->create();

        // create sport types
        SportType::create([
            "name" => "Rennen",
        ]);

        SportType::create([
            "name" => "Velofahren",
        ]);

        SportType::create([
            "name" => "Rollstuhl (mit Begleitung)",
        ]);

        SportType::create([
            "name" => "Andere (bitte spezifizieren)",
        ]);

        // create partners
        Partner::create([
            "name" => "alle zu gleichen Teilen",
        ]);

        Partner::create([
            "name" => "Brühlgut Stiftung Winterthur",
        ]);

        Partner::create([
            "name" => "Institut Kinderseele Schweiz",
        ]);

        Partner::create([
            "name" => "Dargebotene Hand (Tel 143)",
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Athlete;
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

        Athlete::factory(10)->create();

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
    }
}

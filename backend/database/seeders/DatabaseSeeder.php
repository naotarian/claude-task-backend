<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
        ]);

        // Demo data for local development. Never run in production.
        if (! App::environment('production')) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}

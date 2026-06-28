<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $gb = 1024 ** 3;

        $plans = [
            [
                'code' => 'free',
                'name' => 'Free',
                'price_monthly' => 0,
                // ≤3 projects per org, <20 members per project, <3GB storage per project.
                'max_projects' => 3,
                'max_members_per_project' => 19,
                'max_storage_bytes_per_project' => 3 * $gb,
            ],
            [
                'code' => 'pro',
                'name' => 'Pro',
                'price_monthly' => 980,
                'max_projects' => null,
                'max_members_per_project' => null,
                'max_storage_bytes_per_project' => null,
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'price_monthly' => 2980,
                'max_projects' => null,
                'max_members_per_project' => null,
                'max_storage_bytes_per_project' => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}

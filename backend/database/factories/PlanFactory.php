<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(1),
            'name' => fake()->word(),
            'price_monthly' => 0,
            'max_projects' => null,
            'max_members_per_project' => null,
            'max_storage_bytes_per_project' => null,
        ];
    }

    /** An unlimited paid plan. */
    public function paid(): static
    {
        return $this->state(fn () => [
            'code' => 'pro',
            'name' => 'Pro',
            'price_monthly' => 1000,
        ]);
    }
}

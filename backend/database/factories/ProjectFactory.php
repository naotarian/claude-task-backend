<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'key' => Str::upper(Str::random(4)),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'status' => ProjectStatus::Active,
            'categories_enabled' => true,
            'task_sequence' => 0,
        ];
    }
}

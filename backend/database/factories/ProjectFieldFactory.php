<?php

namespace Database\Factories;

use App\Enums\ProjectFieldType;
use App\Models\Project;
use App\Models\ProjectField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectField>
 */
class ProjectFieldFactory extends Factory
{
    protected $model = ProjectField::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->unique()->word(),
            'type' => ProjectFieldType::Text,
            'position' => 0,
            'is_hidden' => false,
        ];
    }

    public function type(ProjectFieldType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}

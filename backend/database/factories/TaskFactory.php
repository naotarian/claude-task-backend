<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $project = Project::factory();

        return [
            'project_id' => $project,
            'task_status_id' => TaskStatus::factory(),
            'seq_number' => fake()->unique()->numberBetween(1, 100000),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'created_by_user_id' => User::factory(),
            'parent_task_id' => null,
            'priority' => TaskPriority::Normal,
            'progress' => 0,
            'estimated_hours' => null,
            'actual_hours' => 0,
        ];
    }
}

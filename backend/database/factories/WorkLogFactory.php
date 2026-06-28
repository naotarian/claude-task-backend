<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkLog>
 */
class WorkLogFactory extends Factory
{
    protected $model = WorkLog::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'worked_on' => now()->toDateString(),
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'note' => fake()->optional()->sentence(),
        ];
    }
}

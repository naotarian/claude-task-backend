<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'project_id' => Project::factory(),
            'uploaded_by_user_id' => User::factory(),
            'disk' => 's3',
            'path' => 'attachments/'.fake()->uuid().'.txt',
            'original_name' => fake()->word().'.txt',
            'size_bytes' => fake()->numberBetween(1000, 1000000),
            'mime_type' => 'text/plain',
        ];
    }
}

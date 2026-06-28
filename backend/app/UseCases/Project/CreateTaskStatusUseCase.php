<?php

namespace App\UseCases\Project;

use App\Enums\TaskStatusCategory;
use App\Models\Project;
use App\Models\TaskStatus;
use App\Repositories\Contracts\TaskStatusRepositoryInterface;
use App\UseCases\AbstractUseCase;

class CreateTaskStatusUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskStatusRepositoryInterface $statuses,
    ) {}

    public function handle(Project $project, string $name, string $color, TaskStatusCategory $category): TaskStatus
    {
        return $this->transaction(fn (): TaskStatus => $this->statuses->create([
            'project_id' => $project->id,
            'name' => $name,
            'color' => $color,
            'category' => $category,
            'position' => $this->statuses->nextPosition($project),
            'is_hidden' => false,
            'is_protected' => false,
        ]));
    }
}

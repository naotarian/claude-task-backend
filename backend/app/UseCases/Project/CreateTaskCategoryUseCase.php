<?php

namespace App\UseCases\Project;

use App\Models\Project;
use App\Models\TaskCategory;
use App\Repositories\Contracts\TaskCategoryRepositoryInterface;
use App\UseCases\AbstractUseCase;

class CreateTaskCategoryUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskCategoryRepositoryInterface $categories,
    ) {}

    public function handle(Project $project, string $name): TaskCategory
    {
        return $this->transaction(fn (): TaskCategory => $this->categories->create([
            'project_id' => $project->id,
            'name' => $name,
            'position' => $this->categories->nextPosition($project),
        ]));
    }
}

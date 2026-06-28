<?php

namespace App\UseCases\Project;

use App\Models\TaskCategory;
use App\Repositories\Contracts\TaskCategoryRepositoryInterface;
use App\UseCases\AbstractUseCase;

class UpdateTaskCategoryUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskCategoryRepositoryInterface $categories,
    ) {}

    public function handle(TaskCategory $category, string $name): TaskCategory
    {
        return $this->transaction(fn (): TaskCategory => $this->categories->update($category, ['name' => $name]));
    }
}

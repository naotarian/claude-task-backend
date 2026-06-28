<?php

namespace App\UseCases\Project;

use App\Models\TaskCategory;
use App\Repositories\Contracts\TaskCategoryRepositoryInterface;
use App\UseCases\AbstractUseCase;

class DeleteTaskCategoryUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskCategoryRepositoryInterface $categories,
    ) {}

    /**
     * Tasks referencing this category fall back to "未割り当て" (null)
     * automatically via the nullOnDelete foreign key.
     */
    public function handle(TaskCategory $category): void
    {
        $this->transaction(fn () => $this->categories->delete($category));
    }
}

<?php

namespace App\UseCases\Project;

use App\Models\Project;
use App\Repositories\Contracts\TaskCategoryRepositoryInterface;
use App\UseCases\AbstractUseCase;

class ReorderTaskCategoriesUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskCategoryRepositoryInterface $categories,
    ) {}

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function handle(Project $project, array $orderedIds): void
    {
        $this->transaction(fn () => $this->categories->reorder($project, $orderedIds));
    }
}

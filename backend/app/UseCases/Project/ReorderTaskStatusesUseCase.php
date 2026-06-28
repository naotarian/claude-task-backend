<?php

namespace App\UseCases\Project;

use App\Models\Project;
use App\Repositories\Contracts\TaskStatusRepositoryInterface;
use App\UseCases\AbstractUseCase;

class ReorderTaskStatusesUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskStatusRepositoryInterface $statuses,
    ) {}

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function handle(Project $project, array $orderedIds): void
    {
        $this->transaction(fn () => $this->statuses->reorder($project, $orderedIds));
    }
}

<?php

namespace App\UseCases\Project;

use App\Models\TaskStatus;
use App\Repositories\Contracts\TaskStatusRepositoryInterface;
use App\UseCases\AbstractUseCase;

class UpdateTaskStatusUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskStatusRepositoryInterface $statuses,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(TaskStatus $status, array $attributes): TaskStatus
    {
        return $this->transaction(fn (): TaskStatus => $this->statuses->update($status, $attributes));
    }
}

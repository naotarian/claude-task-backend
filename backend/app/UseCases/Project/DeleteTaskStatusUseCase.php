<?php

namespace App\UseCases\Project;

use App\Models\TaskStatus;
use App\Repositories\Contracts\TaskStatusRepositoryInterface;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DeleteTaskStatusUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskStatusRepositoryInterface $statuses,
    ) {}

    /**
     * Delete a status, moving its tasks to the project's protected
     * "未割当" bucket. The protected bucket itself cannot be deleted.
     */
    public function handle(TaskStatus $status): void
    {
        if ($status->is_protected) {
            throw ValidationException::withMessages([
                'status' => ['このステータスは削除できません。'],
            ]);
        }

        $this->transaction(function () use ($status): void {
            $unassigned = $this->statuses->unassignedFor($status->project);

            if ($unassigned === null) {
                throw new RuntimeException('Unassigned status not found for project.');
            }

            $this->statuses->reassignTasks($status, $unassigned);
            $this->statuses->delete($status);
        });
    }
}

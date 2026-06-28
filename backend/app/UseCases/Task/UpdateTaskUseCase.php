<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskFieldValueWriter;
use App\UseCases\AbstractUseCase;

class UpdateTaskUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskFieldValueWriter $fieldValues,
    ) {}

    /**
     * Update only the provided attributes (partial update).
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Task $task, array $data): Task
    {
        // Relationship / non-column keys handled separately.
        $assigneeIds = $data['assignee_user_ids'] ?? null;
        unset($data['assignee_user_ids']);

        $customFields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        if (array_key_exists('reporter_user_id', $data)) {
            $data['created_by_user_id'] = $data['reporter_user_id'];
            unset($data['reporter_user_id']);
        }

        return $this->transaction(function () use ($task, $data, $assigneeIds, $customFields): Task {
            if (! empty($data)) {
                $this->tasks->update($task, $data);
            }

            if ($assigneeIds !== null) {
                $task->assignees()->sync($assigneeIds);
            }

            if ($customFields !== null) {
                $this->fieldValues->write($task, $task->project, $customFields);
            }

            return $task->load(['status', 'category', 'assignees', 'creator', 'fieldValues']);
        });
    }
}

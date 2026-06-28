<?php

namespace App\UseCases\Task;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\TaskFieldValueWriter;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;

class CreateTaskUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly TaskFieldValueWriter $fieldValues,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, User $creator, array $data): Task
    {
        $statusId = $data['task_status_id'] ?? $project->statuses()->orderBy('position')->value('id');

        if ($statusId === null) {
            throw ValidationException::withMessages([
                'task_status_id' => ['プロジェクトにステータスがありません。'],
            ]);
        }

        return $this->transaction(function () use ($project, $creator, $data, $statusId): Task {
            $task = $this->tasks->create([
                'project_id' => $project->id,
                'task_status_id' => $statusId,
                'task_category_id' => $data['task_category_id'] ?? null,
                'seq_number' => $this->tasks->nextSequence($project),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                // created_by = reporter (起票者): explicit or the current user.
                'created_by_user_id' => $data['reporter_user_id'] ?? $creator->id,
                'parent_task_id' => $data['parent_task_id'] ?? null,
                'priority' => $data['priority'] ?? TaskPriority::Normal,
                'progress' => $data['progress'] ?? 0,
                'due_date' => $data['due_date'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'planned_end_date' => $data['planned_end_date'] ?? null,
                'actual_start_date' => $data['actual_start_date'] ?? null,
                'actual_end_date' => $data['actual_end_date'] ?? null,
                'estimated_hours' => $data['estimated_hours'] ?? null,
                'actual_hours' => 0,
            ]);

            $task->assignees()->sync($data['assignee_user_ids'] ?? []);
            $this->fieldValues->write($task, $project, $data['custom_fields'] ?? []);

            return $task->load(['status', 'category', 'assignees', 'creator', 'fieldValues']);
        });
    }
}

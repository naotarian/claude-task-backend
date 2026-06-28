<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\WorkLog;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Task
    {
        return Task::query()->create($attributes);
    }

    public function findById(int $id): ?Task
    {
        return Task::query()->find($id);
    }

    public function nextSequence(Project $project): int
    {
        // Lock the project row to avoid duplicate sequence numbers under
        // concurrent task creation, then increment and return.
        $locked = Project::query()->whereKey($project->id)->lockForUpdate()->first();
        $next = $locked->task_sequence + 1;
        $locked->update(['task_sequence' => $next]);

        return $next;
    }

    /**
     * @param  array{status_id?: int|null, assignee_user_id?: int|null, category_id?: int|null, priority?: string|null}  $filters
     * @return Collection<int, Task>
     */
    public function forProject(Project $project, array $filters = []): Collection
    {
        return Task::query()
            ->where('project_id', $project->id)
            ->when($filters['status_id'] ?? null, fn ($q, $statusId) => $q->where('task_status_id', $statusId))
            ->when($filters['category_id'] ?? null, fn ($q, $categoryId) => $q->where('task_category_id', $categoryId))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['assignee_user_id'] ?? null, fn ($q, $assignee) => $q->whereHas('assignees', fn ($a) => $a->where('users.id', $assignee)))
            ->with(['status', 'category', 'assignees', 'creator'])
            ->orderBy('seq_number')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes): Task
    {
        $task->update($attributes);

        return $task;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addComment(Task $task, array $attributes): TaskComment
    {
        return $task->comments()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addWorkLog(Task $task, array $attributes): WorkLog
    {
        return $task->workLogs()->create($attributes);
    }

    public function recomputeActualHours(Task $task): void
    {
        $total = (float) $task->workLogs()->sum('hours');
        $task->update(['actual_hours' => $total]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addAttachment(Task $task, array $attributes): TaskAttachment
    {
        return $task->attachments()->create($attributes);
    }
}

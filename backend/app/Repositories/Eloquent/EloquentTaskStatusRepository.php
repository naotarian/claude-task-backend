<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Repositories\Contracts\TaskStatusRepositoryInterface;

class EloquentTaskStatusRepository implements TaskStatusRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TaskStatus
    {
        return TaskStatus::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TaskStatus $status, array $attributes): TaskStatus
    {
        $status->update($attributes);

        return $status;
    }

    public function delete(TaskStatus $status): void
    {
        $status->delete();
    }

    public function nextPosition(Project $project): int
    {
        return (int) $project->statuses()->max('position') + 1;
    }

    public function unassignedFor(Project $project): ?TaskStatus
    {
        return $project->statuses()->where('is_protected', true)->first();
    }

    public function taskCount(TaskStatus $status): int
    {
        return Task::query()->where('task_status_id', $status->id)->count();
    }

    public function reassignTasks(TaskStatus $from, TaskStatus $to): void
    {
        Task::query()->where('task_status_id', $from->id)->update(['task_status_id' => $to->id]);
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void
    {
        $position = 0;
        foreach ($orderedIds as $id) {
            TaskStatus::query()
                ->where('project_id', $project->id)
                ->where('id', $id)
                ->update(['position' => $position++]);
        }
    }
}

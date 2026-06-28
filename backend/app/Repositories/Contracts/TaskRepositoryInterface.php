<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\WorkLog;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Task;

    public function findById(int $id): ?Task;

    /**
     * Atomically reserve and return the next per-project task number.
     */
    public function nextSequence(Project $project): int;

    /**
     * @param  array{status_id?: int|null, assignee_user_id?: int|null}  $filters
     * @return Collection<int, Task>
     */
    public function forProject(Project $project, array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes): Task;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addComment(Task $task, array $attributes): TaskComment;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addWorkLog(Task $task, array $attributes): WorkLog;

    /**
     * Recalculate the cached actual_hours from the task's work logs.
     */
    public function recomputeActualHours(Task $task): void;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addAttachment(Task $task, array $attributes): TaskAttachment;
}

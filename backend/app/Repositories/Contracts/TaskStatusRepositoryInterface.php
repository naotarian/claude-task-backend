<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\TaskStatus;

interface TaskStatusRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TaskStatus;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TaskStatus $status, array $attributes): TaskStatus;

    public function delete(TaskStatus $status): void;

    public function nextPosition(Project $project): int;

    /** The protected "未割当" bucket of the project. */
    public function unassignedFor(Project $project): ?TaskStatus;

    public function taskCount(TaskStatus $status): int;

    /** Move all tasks from one status to another. */
    public function reassignTasks(TaskStatus $from, TaskStatus $to): void;

    /**
     * Apply the given order (array of status ids) as positions.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void;
}

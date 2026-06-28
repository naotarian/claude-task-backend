<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function __construct(
        private readonly ProjectPolicy $projects,
    ) {}

    public function view(User $user, Task $task): bool
    {
        return $this->projects->view($user, $task->project);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->projects->contribute($user, $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->projects->contribute($user, $task->project);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->projects->contribute($user, $task->project);
    }

    public function logWork(User $user, Task $task): bool
    {
        return $this->projects->contribute($user, $task->project);
    }
}

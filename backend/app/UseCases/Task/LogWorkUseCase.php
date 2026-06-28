<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\UseCases\AbstractUseCase;

/**
 * Records actual effort (hours) against a task and refreshes the task's
 * cached actual_hours (実績工数) from the sum of its work logs.
 */
class LogWorkUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function handle(Task $task, User $user, string $workedOn, float $hours, ?string $note): WorkLog
    {
        return $this->transaction(function () use ($task, $user, $workedOn, $hours, $note): WorkLog {
            $log = $this->tasks->addWorkLog($task, [
                'user_id' => $user->id,
                'worked_on' => $workedOn,
                'hours' => $hours,
                'note' => $note,
            ]);

            $this->tasks->recomputeActualHours($task);

            return $log->load('user');
        });
    }
}

<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\UseCases\AbstractUseCase;

class AddCommentUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function handle(Task $task, User $user, string $body): TaskComment
    {
        return $this->transaction(function () use ($task, $user, $body): TaskComment {
            $comment = $this->tasks->addComment($task, [
                'user_id' => $user->id,
                'body' => $body,
            ]);

            return $comment->load('user');
        });
    }
}

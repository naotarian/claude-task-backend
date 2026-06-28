<?php

namespace App\Data;

use App\Models\TaskComment;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskCommentData extends Data
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public string $body,
        public string $createdAt,
    ) {}

    public static function fromModel(TaskComment $comment): self
    {
        return new self(
            id: $comment->id,
            userId: $comment->user_id,
            userName: $comment->user->name,
            body: $comment->body,
            createdAt: $comment->created_at->toIso8601String(),
        );
    }
}

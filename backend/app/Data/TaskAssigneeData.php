<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskAssigneeData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $avatarPath,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            avatarPath: $user->avatar_path,
        );
    }
}

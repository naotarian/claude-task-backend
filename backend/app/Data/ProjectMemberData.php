<?php

namespace App\Data;

use App\Enums\ProjectRole;
use App\Models\ProjectMember;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectMemberData extends Data
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public string $email,
        public ProjectRole $role,
    ) {}

    public static function fromModel(ProjectMember $member): self
    {
        return new self(
            id: $member->id,
            userId: $member->user_id,
            name: $member->user->name,
            email: $member->user->email,
            role: $member->role,
        );
    }
}

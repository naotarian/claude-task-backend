<?php

namespace App\Data;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrganizationMemberData extends Data
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public string $email,
        public OrganizationRole $role,
        public ?int $positionId,
        public ?string $positionName,
    ) {}

    public static function fromModel(OrganizationMember $member): self
    {
        return new self(
            id: $member->id,
            userId: $member->user_id,
            name: $member->user->name,
            email: $member->user->email,
            role: $member->role,
            positionId: $member->position_id,
            positionName: $member->relationLoaded('position') ? $member->position?->name : null,
        );
    }
}

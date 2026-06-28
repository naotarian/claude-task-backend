<?php

namespace App\Data;

use App\Enums\OrganizationRole;
use App\Models\OrganizationInvitation;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrganizationInvitationData extends Data
{
    public function __construct(
        public int $id,
        public string $email,
        public OrganizationRole $role,
        public string $expiresAt,
        public bool $accepted,
    ) {}

    public static function fromModel(OrganizationInvitation $invitation): self
    {
        return new self(
            id: $invitation->id,
            email: $invitation->email,
            role: $invitation->role,
            expiresAt: $invitation->expires_at->toIso8601String(),
            accepted: $invitation->isAccepted(),
        );
    }
}

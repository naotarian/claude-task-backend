<?php

namespace App\Data;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Models\Organization;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrganizationData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public OrganizationType $type,
        /** The authenticated user's role in this organization, when known. */
        public ?OrganizationRole $role,
    ) {}

    public static function fromModel(Organization $organization, ?OrganizationRole $role = null): self
    {
        return new self(
            id: $organization->id,
            name: $organization->name,
            slug: $organization->slug,
            type: $organization->type,
            role: $role,
        );
    }
}

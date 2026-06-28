<?php

namespace App\Data;

use App\Models\OrganizationPosition;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrganizationPositionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromModel(OrganizationPosition $position): self
    {
        return new self(
            id: $position->id,
            name: $position->name,
        );
    }
}

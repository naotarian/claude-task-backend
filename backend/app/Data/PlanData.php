<?php

namespace App\Data;

use App\Models\Plan;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PlanData extends Data
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $priceMonthly,
        public ?int $maxProjects,
        public ?int $maxMembersPerProject,
        public ?int $maxStorageBytesPerProject,
    ) {}

    public static function fromModel(Plan $plan): self
    {
        return new self(
            id: $plan->id,
            code: $plan->code,
            name: $plan->name,
            priceMonthly: $plan->price_monthly,
            maxProjects: $plan->max_projects,
            maxMembersPerProject: $plan->max_members_per_project,
            maxStorageBytesPerProject: $plan->max_storage_bytes_per_project,
        );
    }
}

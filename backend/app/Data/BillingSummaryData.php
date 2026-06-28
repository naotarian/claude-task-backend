<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BillingSummaryData extends Data
{
    public function __construct(
        public PlanData $plan,
        public int $projectCount,
    ) {}
}

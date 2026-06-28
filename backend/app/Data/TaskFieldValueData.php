<?php

namespace App\Data;

use App\Models\TaskFieldValue;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskFieldValueData extends Data
{
    public function __construct(
        public int $fieldId,
        public mixed $value,
    ) {}

    public static function fromModel(TaskFieldValue $value): self
    {
        return new self(
            fieldId: $value->project_field_id,
            value: $value->value,
        );
    }
}

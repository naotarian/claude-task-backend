<?php

namespace App\Data;

use App\Enums\TaskStatusCategory;
use App\Models\TaskStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskStatusData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $color,
        public TaskStatusCategory $category,
        public int $position,
        public bool $isHidden,
        public bool $isProtected,
    ) {}

    public static function fromModel(TaskStatus $status): self
    {
        return new self(
            id: $status->id,
            name: $status->name,
            color: $status->color,
            category: $status->category,
            position: $status->position,
            isHidden: $status->is_hidden,
            isProtected: $status->is_protected,
        );
    }
}

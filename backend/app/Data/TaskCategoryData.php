<?php

namespace App\Data;

use App\Models\TaskCategory;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $position,
    ) {}

    public static function fromModel(TaskCategory $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            position: $category->position,
        );
    }
}

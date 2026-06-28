<?php

namespace App\Data;

use App\Models\ProjectFieldOption;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectFieldOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
    ) {}

    public static function fromModel(ProjectFieldOption $option): self
    {
        return new self(
            id: $option->id,
            label: $option->label,
        );
    }
}

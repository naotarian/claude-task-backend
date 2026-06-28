<?php

namespace App\Data;

use App\Enums\ProjectFieldType;
use App\Models\ProjectField;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectFieldData extends Data
{
    /**
     * @param  array<int, ProjectFieldOptionData>  $options
     */
    public function __construct(
        public int $id,
        public string $name,
        public ProjectFieldType $type,
        public int $position,
        public bool $isHidden,
        #[DataCollectionOf(ProjectFieldOptionData::class)]
        public array $options,
    ) {}

    public static function fromModel(ProjectField $field): self
    {
        return new self(
            id: $field->id,
            name: $field->name,
            type: $field->type,
            position: $field->position,
            isHidden: $field->is_hidden,
            options: $field->relationLoaded('options')
                ? $field->options->map(fn ($o) => ProjectFieldOptionData::fromModel($o))->all()
                : [],
        );
    }
}

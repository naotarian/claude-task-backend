<?php

namespace App\Data;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProjectData extends Data
{
    /**
     * @param  array<int, TaskStatusData>|null  $statuses
     */
    public function __construct(
        public int $id,
        public int $organizationId,
        public string $key,
        public string $name,
        public ?string $description,
        public ProjectStatus $status,
        public bool $categoriesEnabled,
        /** The authenticated user's role in this project, when known. */
        public ?ProjectRole $role,
        #[DataCollectionOf(TaskStatusData::class)]
        public ?array $statuses = null,
        /** Visible custom fields (definitions), when loaded. */
        #[DataCollectionOf(ProjectFieldData::class)]
        public ?array $fields = null,
        /** Task categories, when loaded. */
        #[DataCollectionOf(TaskCategoryData::class)]
        public ?array $categories = null,
    ) {}

    public static function fromModel(Project $project, ?ProjectRole $role = null, bool $withStatuses = false): self
    {
        return new self(
            id: $project->id,
            organizationId: $project->organization_id,
            key: $project->key,
            name: $project->name,
            description: $project->description,
            status: $project->status,
            categoriesEnabled: $project->categories_enabled,
            role: $role,
            statuses: $withStatuses
                ? $project->statuses->map(fn ($s) => TaskStatusData::fromModel($s))->all()
                : null,
            fields: $withStatuses && $project->relationLoaded('fields')
                ? $project->fields->where('is_hidden', false)->values()->map(fn ($f) => ProjectFieldData::fromModel($f))->all()
                : null,
            categories: $withStatuses && $project->relationLoaded('categories')
                ? $project->categories->map(fn ($c) => TaskCategoryData::fromModel($c))->all()
                : null,
        );
    }
}

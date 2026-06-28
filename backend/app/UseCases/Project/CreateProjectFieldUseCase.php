<?php

namespace App\UseCases\Project;

use App\Enums\ProjectFieldType;
use App\Models\Project;
use App\Models\ProjectField;
use App\Repositories\Contracts\ProjectFieldRepositoryInterface;
use App\UseCases\AbstractUseCase;

class CreateProjectFieldUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectFieldRepositoryInterface $fields,
    ) {}

    /**
     * @param  array<int, string>  $options
     */
    public function handle(Project $project, string $name, ProjectFieldType $type, array $options = []): ProjectField
    {
        return $this->transaction(function () use ($project, $name, $type, $options): ProjectField {
            $field = $this->fields->create([
                'project_id' => $project->id,
                'name' => $name,
                'type' => $type,
                'position' => $this->fields->nextPosition($project),
                'is_hidden' => false,
            ]);

            if ($type->hasOptions()) {
                $this->fields->replaceOptions($field, $options);
            }

            return $field->load('options');
        });
    }
}

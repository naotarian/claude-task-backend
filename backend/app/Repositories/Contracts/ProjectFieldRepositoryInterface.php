<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\ProjectField;
use Illuminate\Support\Collection;

interface ProjectFieldRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ProjectField;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ProjectField $field, array $attributes): ProjectField;

    public function delete(ProjectField $field): void;

    public function nextPosition(Project $project): int;

    /**
     * @return Collection<int, ProjectField>
     */
    public function forProject(Project $project): Collection;

    public function findForProject(Project $project, int $fieldId): ?ProjectField;

    /**
     * Replace a field's options with the given labels (for select types).
     *
     * @param  array<int, string>  $labels
     */
    public function replaceOptions(ProjectField $field, array $labels): void;

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void;
}

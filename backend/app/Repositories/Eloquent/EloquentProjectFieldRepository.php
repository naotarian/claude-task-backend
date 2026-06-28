<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\ProjectField;
use App\Repositories\Contracts\ProjectFieldRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentProjectFieldRepository implements ProjectFieldRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ProjectField
    {
        return ProjectField::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ProjectField $field, array $attributes): ProjectField
    {
        $field->update($attributes);

        return $field;
    }

    public function delete(ProjectField $field): void
    {
        $field->delete();
    }

    public function nextPosition(Project $project): int
    {
        return (int) $project->fields()->max('position') + 1;
    }

    /**
     * @return Collection<int, ProjectField>
     */
    public function forProject(Project $project): Collection
    {
        return $project->fields()->with('options')->get();
    }

    public function findForProject(Project $project, int $fieldId): ?ProjectField
    {
        return $project->fields()->with('options')->whereKey($fieldId)->first();
    }

    /**
     * @param  array<int, string>  $labels
     */
    public function replaceOptions(ProjectField $field, array $labels): void
    {
        $field->options()->delete();
        foreach (array_values($labels) as $position => $label) {
            $field->options()->create(['label' => $label, 'position' => $position]);
        }
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void
    {
        $position = 0;
        foreach ($orderedIds as $id) {
            ProjectField::query()
                ->where('project_id', $project->id)
                ->where('id', $id)
                ->update(['position' => $position++]);
        }
    }
}

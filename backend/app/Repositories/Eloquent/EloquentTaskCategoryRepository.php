<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\TaskCategory;
use App\Repositories\Contracts\TaskCategoryRepositoryInterface;

class EloquentTaskCategoryRepository implements TaskCategoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TaskCategory
    {
        return TaskCategory::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TaskCategory $category, array $attributes): TaskCategory
    {
        $category->update($attributes);

        return $category;
    }

    public function delete(TaskCategory $category): void
    {
        $category->delete();
    }

    public function nextPosition(Project $project): int
    {
        return (int) $project->categories()->max('position') + 1;
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void
    {
        $position = 0;
        foreach ($orderedIds as $id) {
            TaskCategory::query()
                ->where('project_id', $project->id)
                ->where('id', $id)
                ->update(['position' => $position++]);
        }
    }
}

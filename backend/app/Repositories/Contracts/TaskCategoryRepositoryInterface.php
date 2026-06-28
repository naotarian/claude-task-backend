<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\TaskCategory;

interface TaskCategoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TaskCategory;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TaskCategory $category, array $attributes): TaskCategory;

    public function delete(TaskCategory $category): void;

    public function nextPosition(Project $project): int;

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void;
}

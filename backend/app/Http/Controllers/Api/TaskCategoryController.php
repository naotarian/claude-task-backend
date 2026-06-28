<?php

namespace App\Http\Controllers\Api;

use App\Data\TaskCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ReorderTaskCategoriesRequest;
use App\Http\Requests\Project\StoreTaskCategoryRequest;
use App\Models\Project;
use App\Models\TaskCategory;
use App\UseCases\Project\CreateTaskCategoryUseCase;
use App\UseCases\Project\DeleteTaskCategoryUseCase;
use App\UseCases\Project\ReorderTaskCategoriesUseCase;
use App\UseCases\Project\UpdateTaskCategoryUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TaskCategoryController extends Controller
{
    public function store(StoreTaskCategoryRequest $request, Project $project, CreateTaskCategoryUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $category = $useCase->handle($project, $request->string('name')->toString());

        return TaskCategoryData::fromModel($category)->toResponse($request)->setStatusCode(201);
    }

    public function update(StoreTaskCategoryRequest $request, Project $project, TaskCategory $category, UpdateTaskCategoryUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);
        abort_unless($category->project_id === $project->id, 404);

        $updated = $useCase->handle($category, $request->string('name')->toString());

        return TaskCategoryData::fromModel($updated)->toResponse($request)->setStatusCode(200);
    }

    public function destroy(Project $project, TaskCategory $category, DeleteTaskCategoryUseCase $useCase): Response
    {
        $this->authorize('manage', $project);
        abort_unless($category->project_id === $project->id, 404);

        $useCase->handle($category);

        return response()->noContent();
    }

    public function reorder(ReorderTaskCategoriesRequest $request, Project $project, ReorderTaskCategoriesUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $useCase->handle($project, $request->ids());

        return response()->json(['message' => 'reordered']);
    }
}

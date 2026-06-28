<?php

namespace App\Http\Controllers\Api;

use App\Data\TaskStatusData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ReorderTaskStatusesRequest;
use App\Http\Requests\Project\StoreTaskStatusRequest;
use App\Http\Requests\Project\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\TaskStatus;
use App\UseCases\Project\CreateTaskStatusUseCase;
use App\UseCases\Project\DeleteTaskStatusUseCase;
use App\UseCases\Project\ReorderTaskStatusesUseCase;
use App\UseCases\Project\UpdateTaskStatusUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TaskStatusController extends Controller
{
    public function store(StoreTaskStatusRequest $request, Project $project, CreateTaskStatusUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $status = $useCase->handle(
            project: $project,
            name: $request->string('name')->toString(),
            color: $request->color(),
            category: $request->category(),
        );

        return TaskStatusData::fromModel($status)->toResponse($request)->setStatusCode(201);
    }

    public function update(UpdateTaskStatusRequest $request, Project $project, TaskStatus $status, UpdateTaskStatusUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);
        abort_unless($status->project_id === $project->id, 404);

        $updated = $useCase->handle($status, $request->payload());

        return TaskStatusData::fromModel($updated)->toResponse($request)->setStatusCode(200);
    }

    public function destroy(Project $project, TaskStatus $status, DeleteTaskStatusUseCase $useCase): Response
    {
        $this->authorize('manage', $project);
        abort_unless($status->project_id === $project->id, 404);

        $useCase->handle($status);

        return response()->noContent();
    }

    public function reorder(ReorderTaskStatusesRequest $request, Project $project, ReorderTaskStatusesUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $useCase->handle($project, $request->ids());

        return response()->json(['message' => 'reordered']);
    }
}

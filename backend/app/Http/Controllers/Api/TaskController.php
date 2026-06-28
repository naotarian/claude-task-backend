<?php

namespace App\Http\Controllers\Api;

use App\Data\TaskData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\UseCases\Task\CreateTaskUseCase;
use App\UseCases\Task\DeleteTaskUseCase;
use App\UseCases\Task\UpdateTaskUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

class TaskController extends Controller
{
    public function index(Request $request, Project $project, TaskRepositoryInterface $tasks): DataCollection
    {
        $this->authorize('view', $project);

        $filters = [
            'status_id' => $request->integer('status_id') ?: null,
            'assignee_user_id' => $request->integer('assignee_user_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'priority' => $request->string('priority')->toString() ?: null,
        ];

        $data = $tasks->forProject($project, $filters)
            ->map(fn (Task $task) => TaskData::fromModel($task))
            ->all();

        return new DataCollection(TaskData::class, $data);
    }

    public function store(StoreTaskRequest $request, Project $project, CreateTaskUseCase $useCase): JsonResponse
    {
        $this->authorize('contribute', $project);

        $task = $useCase->handle($project, $request->user(), $request->payload());

        return TaskData::fromModel($task)->toResponse($request)->setStatusCode(201);
    }

    public function show(Request $request, Task $task): TaskData
    {
        $this->authorize('view', $task);

        $task->load(['status', 'category', 'assignees', 'creator', 'fieldValues']);

        return TaskData::fromModel($task);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskUseCase $useCase): JsonResponse
    {
        $this->authorize('update', $task);

        $updated = $useCase->handle($task, $request->payload());

        return TaskData::fromModel($updated)->toResponse($request)->setStatusCode(200);
    }

    public function destroy(Task $task, DeleteTaskUseCase $useCase): Response
    {
        $this->authorize('delete', $task);

        $useCase->handle($task);

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Data\TaskCommentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\AddCommentRequest;
use App\Models\Task;
use App\UseCases\Task\AddCommentUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class TaskCommentController extends Controller
{
    public function index(Request $request, Task $task): DataCollection
    {
        $this->authorize('view', $task);

        $comments = $task->comments()->with('user')->orderBy('created_at')->get()
            ->map(fn ($comment) => TaskCommentData::fromModel($comment))
            ->all();

        return new DataCollection(TaskCommentData::class, $comments);
    }

    public function store(AddCommentRequest $request, Task $task, AddCommentUseCase $useCase): JsonResponse
    {
        $this->authorize('comment', $task);

        $comment = $useCase->handle($task, $request->user(), $request->string('body')->toString());

        return TaskCommentData::fromModel($comment)->toResponse($request)->setStatusCode(201);
    }
}

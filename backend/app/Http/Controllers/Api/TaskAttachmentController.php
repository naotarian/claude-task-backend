<?php

namespace App\Http\Controllers\Api;

use App\Data\TaskAttachmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\UploadAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\UseCases\Task\UploadAttachmentUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\DataCollection;

class TaskAttachmentController extends Controller
{
    public function index(Request $request, Task $task): DataCollection
    {
        $this->authorize('view', $task);

        $attachments = $task->attachments()->with('uploadedBy')->orderByDesc('created_at')->get()
            ->map(fn ($a) => TaskAttachmentData::fromModel($a))
            ->all();

        return new DataCollection(TaskAttachmentData::class, $attachments);
    }

    public function store(UploadAttachmentRequest $request, Task $task, UploadAttachmentUseCase $useCase): JsonResponse
    {
        $this->authorize('update', $task);

        $attachment = $useCase->handle($task, $request->user(), $request->file('file'));

        return TaskAttachmentData::fromModel($attachment)->toResponse($request)->setStatusCode(201);
    }

    public function destroy(Task $task, TaskAttachment $attachment): Response
    {
        $this->authorize('update', $task);

        abort_unless($attachment->task_id === $task->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Data\WorkLogData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\LogWorkRequest;
use App\Models\Task;
use App\UseCases\Task\LogWorkUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class WorkLogController extends Controller
{
    public function index(Request $request, Task $task): DataCollection
    {
        $this->authorize('view', $task);

        $logs = $task->workLogs()->with('user')->orderByDesc('worked_on')->get()
            ->map(fn ($log) => WorkLogData::fromModel($log))
            ->all();

        return new DataCollection(WorkLogData::class, $logs);
    }

    public function store(LogWorkRequest $request, Task $task, LogWorkUseCase $useCase): JsonResponse
    {
        $this->authorize('logWork', $task);

        $log = $useCase->handle(
            task: $task,
            user: $request->user(),
            workedOn: $request->string('worked_on')->toString(),
            hours: (float) $request->input('hours'),
            note: $request->input('note'),
        );

        return WorkLogData::fromModel($log)->toResponse($request)->setStatusCode(201);
    }
}

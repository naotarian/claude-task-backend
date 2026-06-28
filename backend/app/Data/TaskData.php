<?php

namespace App\Data;

use App\Enums\TaskPriority;
use App\Models\Task;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskData extends Data
{
    /**
     * @param  array<int, TaskAssigneeData>  $assignees
     */
    public function __construct(
        public int $id,
        public int $projectId,
        public int $taskStatusId,
        public int $seqNumber,
        public string $title,
        public ?string $description,
        public ?int $categoryId,
        public ?string $categoryName,
        #[DataCollectionOf(TaskAssigneeData::class)]
        public array $assignees,
        public ?int $reporterId,
        public ?string $reporterName,
        public ?int $parentTaskId,
        public TaskPriority $priority,
        public int $progress,
        // Schedule (plan vs actual) — ISO date strings
        public ?string $dueDate,
        public ?string $plannedStartDate,
        public ?string $plannedEndDate,
        public ?string $actualStartDate,
        public ?string $actualEndDate,
        // Effort (hours)
        public ?float $estimatedHours,
        public float $actualHours,
        /** @var array<TaskFieldValueData> */
        #[DataCollectionOf(TaskFieldValueData::class)]
        public array $customFields,
    ) {}

    public static function fromModel(Task $task): self
    {
        return new self(
            id: $task->id,
            projectId: $task->project_id,
            taskStatusId: $task->task_status_id,
            seqNumber: $task->seq_number,
            title: $task->title,
            description: $task->description,
            categoryId: $task->task_category_id,
            categoryName: $task->relationLoaded('category') ? $task->category?->name : null,
            assignees: $task->relationLoaded('assignees')
                ? $task->assignees->map(fn ($u) => TaskAssigneeData::fromModel($u))->all()
                : [],
            reporterId: $task->created_by_user_id,
            reporterName: $task->relationLoaded('creator') ? $task->creator?->name : null,
            parentTaskId: $task->parent_task_id,
            priority: $task->priority,
            progress: $task->progress,
            dueDate: $task->due_date?->toDateString(),
            plannedStartDate: $task->planned_start_date?->toDateString(),
            plannedEndDate: $task->planned_end_date?->toDateString(),
            actualStartDate: $task->actual_start_date?->toDateString(),
            actualEndDate: $task->actual_end_date?->toDateString(),
            estimatedHours: $task->estimated_hours !== null ? (float) $task->estimated_hours : null,
            actualHours: (float) $task->actual_hours,
            customFields: $task->relationLoaded('fieldValues')
                ? $task->fieldValues->map(fn ($v) => TaskFieldValueData::fromModel($v))->all()
                : [],
        );
    }
}

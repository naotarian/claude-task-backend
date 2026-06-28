<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Task $task */
        $task = $this->route('task');
        $projectId = $task->project_id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_status_id' => ['sometimes', 'required', Rule::exists('task_statuses', 'id')->where('project_id', $projectId)],
            'task_category_id' => ['sometimes', 'nullable', Rule::exists('task_categories', 'id')->where('project_id', $projectId)],
            'assignee_user_ids' => ['sometimes', 'array'],
            'assignee_user_ids.*' => [Rule::exists('project_members', 'user_id')->where('project_id', $projectId)],
            'reporter_user_id' => ['sometimes', 'required', Rule::exists('project_members', 'user_id')->where('project_id', $projectId)],
            'parent_task_id' => ['nullable', Rule::exists('tasks', 'id')->where('project_id', $projectId)],
            'priority' => ['sometimes', 'required', new Enum(TaskPriority::class)],
            'progress' => ['sometimes', 'required', 'integer', 'between:0,100'],
            'due_date' => ['nullable', 'date'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'actual_start_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:actual_start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'custom_fields' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();

        if (isset($validated['priority'])) {
            $validated['priority'] = TaskPriority::from($validated['priority']);
        }

        return $validated;
    }
}

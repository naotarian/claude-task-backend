<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
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
        /** @var Project $project */
        $project = $this->route('project');
        $projectId = $project->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_status_id' => ['nullable', Rule::exists('task_statuses', 'id')->where('project_id', $projectId)],
            'task_category_id' => ['nullable', Rule::exists('task_categories', 'id')->where('project_id', $projectId)],
            'assignee_user_ids' => ['nullable', 'array'],
            'assignee_user_ids.*' => [Rule::exists('project_members', 'user_id')->where('project_id', $projectId)],
            'reporter_user_id' => ['nullable', Rule::exists('project_members', 'user_id')->where('project_id', $projectId)],
            'parent_task_id' => ['nullable', Rule::exists('tasks', 'id')->where('project_id', $projectId)],
            'priority' => ['nullable', new Enum(TaskPriority::class)],
            'progress' => ['nullable', 'integer', 'between:0,100'],
            'due_date' => ['nullable', 'date'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'actual_start_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:actual_start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'custom_fields' => ['nullable', 'array'],
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

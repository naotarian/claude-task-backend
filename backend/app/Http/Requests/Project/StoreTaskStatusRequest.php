<?php

namespace App\Http\Requests\Project;

use App\Enums\TaskStatusCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskStatusRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'category' => ['nullable', new Enum(TaskStatusCategory::class)],
        ];
    }

    public function category(): TaskStatusCategory
    {
        return $this->filled('category')
            ? TaskStatusCategory::from($this->string('category')->toString())
            : TaskStatusCategory::Todo;
    }

    public function color(): string
    {
        return $this->filled('color') ? $this->string('color')->toString() : '#9ca3af';
    }
}

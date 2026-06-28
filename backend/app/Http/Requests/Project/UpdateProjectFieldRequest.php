<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectFieldRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_hidden' => ['sometimes', 'boolean'],
            'options' => ['sometimes', 'array'],
            'options.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesToUpdate(): array
    {
        return $this->only(['name', 'is_hidden']);
    }

    /**
     * @return array<int, string>|null
     */
    public function options(): ?array
    {
        if (! $this->has('options')) {
            return null;
        }

        return array_values(array_map('strval', $this->input('options', [])));
    }
}

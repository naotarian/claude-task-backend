<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreProjectFieldRequest extends FormRequest
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
        $needsOptions = in_array($this->input('type'), [
            ProjectFieldType::Select->value,
            ProjectFieldType::Multiselect->value,
        ], true);

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ProjectFieldType::class)],
            'options' => [Rule::requiredIf($needsOptions), 'array'],
            'options.*' => ['string', 'max:255'],
        ];
    }

    public function fieldType(): ProjectFieldType
    {
        return ProjectFieldType::from($this->string('type')->toString());
    }

    /**
     * @return array<int, string>
     */
    public function options(): array
    {
        return array_values(array_map('strval', $this->input('options', [])));
    }
}

<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
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
            // Max 50MB per file at the HTTP layer; the plan storage quota is
            // enforced separately in the use case.
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}

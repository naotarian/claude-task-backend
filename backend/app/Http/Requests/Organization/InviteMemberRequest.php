<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class InviteMemberRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', new Enum(OrganizationRole::class), Rule::notIn([OrganizationRole::Owner->value])],
        ];
    }

    public function role(): OrganizationRole
    {
        return OrganizationRole::from($this->string('role')->toString());
    }
}

<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateMemberRequest extends FormRequest
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
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'role' => ['sometimes', new Enum(OrganizationRole::class)],
            'position_id' => [
                'sometimes',
                'nullable',
                Rule::exists('organization_positions', 'id')->where('organization_id', $organization->id),
            ],
        ];
    }

    /**
     * Only the provided fields, with role cast to the enum.
     *
     * @return array{role?: OrganizationRole, position_id?: int|null}
     */
    public function changes(): array
    {
        $changes = [];
        if ($this->has('role')) {
            $changes['role'] = OrganizationRole::from($this->string('role')->toString());
        }
        if ($this->has('position_id')) {
            $value = $this->input('position_id');
            $changes['position_id'] = $value === null ? null : (int) $value;
        }

        return $changes;
    }
}

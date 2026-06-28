<?php

namespace App\UseCases\Organization;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;

class UpdateOrganizationMemberUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    /**
     * Update a member's organization role and/or job-title position.
     *
     * @param  array{role?: OrganizationRole, position_id?: int|null}  $changes
     */
    public function handle(OrganizationMember $member, array $changes): OrganizationMember
    {
        // Guard: never leave the organization without an owner.
        if (array_key_exists('role', $changes)
            && $member->role === OrganizationRole::Owner
            && $changes['role'] !== OrganizationRole::Owner
            && $this->organizations->ownerCount($member->organization) <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => ['組織には最低1人のオーナーが必要です。'],
            ]);
        }

        return $this->transaction(function () use ($member, $changes): OrganizationMember {
            if (array_key_exists('role', $changes)) {
                $member->role = $changes['role'];
            }
            if (array_key_exists('position_id', $changes)) {
                $member->position_id = $changes['position_id'];
            }
            $member->save();

            return $member->load(['user', 'position']);
        });
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\OrganizationPosition;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Organization
    {
        return Organization::query()->create($attributes);
    }

    public function findById(int $id): ?Organization
    {
        return Organization::query()->find($id);
    }

    public function findBySlug(string $slug): ?Organization
    {
        return Organization::query()->where('slug', $slug)->first();
    }

    public function addMember(Organization $organization, User $user, OrganizationRole $role): OrganizationMember
    {
        return OrganizationMember::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, Organization>
     */
    public function forUser(User $user): Collection
    {
        return Organization::query()
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();
    }

    public function isMember(Organization $organization, User $user): bool
    {
        return $organization->members()->where('user_id', $user->id)->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createInvitation(array $attributes): OrganizationInvitation
    {
        return OrganizationInvitation::query()->create($attributes);
    }

    public function findInvitationByToken(string $token): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()->where('token', $token)->first();
    }

    public function findPendingInvitation(Organization $organization, string $email): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();
    }

    public function ownerCount(Organization $organization): int
    {
        return $organization->members()->where('role', OrganizationRole::Owner->value)->count();
    }

    public function createPosition(Organization $organization, string $name): OrganizationPosition
    {
        $nextPosition = (int) $organization->positions()->max('position') + 1;

        return OrganizationPosition::query()->create([
            'organization_id' => $organization->id,
            'name' => $name,
            'position' => $nextPosition,
        ]);
    }

    public function deletePosition(OrganizationPosition $position): void
    {
        $position->delete();
    }

    /**
     * @return Collection<int, OrganizationPosition>
     */
    public function positionsForOrganization(Organization $organization): Collection
    {
        return $organization->positions()->get();
    }
}

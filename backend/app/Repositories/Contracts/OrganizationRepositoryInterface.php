<?php

namespace App\Repositories\Contracts;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\OrganizationPosition;
use App\Models\User;
use Illuminate\Support\Collection;

interface OrganizationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Organization;

    public function findById(int $id): ?Organization;

    public function findBySlug(string $slug): ?Organization;

    public function addMember(Organization $organization, User $user, OrganizationRole $role): OrganizationMember;

    /**
     * Organizations the given user belongs to.
     *
     * @return Collection<int, Organization>
     */
    public function forUser(User $user): Collection;

    public function isMember(Organization $organization, User $user): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createInvitation(array $attributes): OrganizationInvitation;

    public function findInvitationByToken(string $token): ?OrganizationInvitation;

    public function findPendingInvitation(Organization $organization, string $email): ?OrganizationInvitation;

    public function ownerCount(Organization $organization): int;

    public function createPosition(Organization $organization, string $name): OrganizationPosition;

    public function deletePosition(OrganizationPosition $position): void;

    /**
     * @return Collection<int, OrganizationPosition>
     */
    public function positionsForOrganization(Organization $organization): Collection;
}

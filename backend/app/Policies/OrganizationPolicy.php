<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->isMemberOf($organization);
    }

    public function manage(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization)?->canManage() ?? false;
    }

    public function inviteMembers(User $user, Organization $organization): bool
    {
        return $this->manage($user, $organization);
    }

    /** Only owners may change member roles / grant or revoke ownership. */
    public function manageOwners(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Owner;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) === OrganizationRole::Owner;
    }
}

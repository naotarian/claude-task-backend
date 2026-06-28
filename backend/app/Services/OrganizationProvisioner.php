<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Creates an organization together with its owner membership.
 *
 * Reused both when a freelancer signs up (personal organization) and when a
 * user explicitly creates a team organization. Does NOT open its own
 * transaction; it runs inside the calling use case's transaction.
 */
class OrganizationProvisioner
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    public function provision(string $name, OrganizationType $type, User $owner): Organization
    {
        $organization = $this->organizations->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'type' => $type,
            'owner_user_id' => $owner->id,
        ]);

        $this->organizations->addMember($organization, $owner, OrganizationRole::Owner);

        return $organization;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';

        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while ($this->organizations->findBySlug($slug) !== null);

        return $slug;
    }
}

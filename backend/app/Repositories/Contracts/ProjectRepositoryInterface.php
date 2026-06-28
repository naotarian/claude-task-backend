<?php

namespace App\Repositories\Contracts;

use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Project;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project;

    public function findById(int $id): ?Project;

    public function addMember(Project $project, User $user, ProjectRole $role): ProjectMember;

    /**
     * @return Collection<int, Project>
     */
    public function forOrganization(Organization $organization, bool $includeArchived = false): Collection;

    public function keyExists(Organization $organization, string $key): bool;

    public function isMember(Project $project, User $user): bool;

    public function memberCount(Project $project): int;

    public function ownerCount(Project $project): int;

    public function activeCountForOrganization(Organization $organization): int;

    /**
     * Total bytes of attachments stored across the project (for the storage quota).
     */
    public function storageBytesForProject(Project $project): int;
}

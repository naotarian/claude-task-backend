<?php

namespace App\Repositories\Eloquent;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Organization;
// ProjectRole/ProjectStatus enums used for role and status filters below.
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Project
    {
        return Project::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project
    {
        $project->update($attributes);

        return $project;
    }

    public function findById(int $id): ?Project
    {
        return Project::query()->find($id);
    }

    public function addMember(Project $project, User $user, ProjectRole $role): ProjectMember
    {
        return ProjectMember::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    /**
     * @return Collection<int, Project>
     */
    public function forOrganization(Organization $organization, bool $includeArchived = false): Collection
    {
        return Project::query()
            ->where('organization_id', $organization->id)
            ->when(! $includeArchived, fn ($q) => $q->where('status', ProjectStatus::Active->value))
            ->orderBy('name')
            ->get();
    }

    public function keyExists(Organization $organization, string $key): bool
    {
        return Project::query()
            ->where('organization_id', $organization->id)
            ->where('key', $key)
            ->exists();
    }

    public function isMember(Project $project, User $user): bool
    {
        return $project->members()->where('user_id', $user->id)->exists();
    }

    public function memberCount(Project $project): int
    {
        return $project->members()->count();
    }

    public function ownerCount(Project $project): int
    {
        return $project->members()->where('role', ProjectRole::Owner->value)->count();
    }

    public function activeCountForOrganization(Organization $organization): int
    {
        return Project::query()
            ->where('organization_id', $organization->id)
            ->where('status', ProjectStatus::Active->value)
            ->count();
    }

    public function storageBytesForProject(Project $project): int
    {
        return (int) TaskAttachment::query()
            ->where('project_id', $project->id)
            ->sum('size_bytes');
    }
}

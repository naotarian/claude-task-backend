<?php

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\Organization;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

/**
 * Enforces plan quotas. Limits always come from the project's *owning*
 * organization's current plan. A null plan (or null limit) means the free
 * tier / unlimited respectively.
 */
class QuotaService
{
    // Free-tier fallbacks, used when an organization has no plan assigned.
    private const FREE_MAX_PROJECTS = 3;

    private const FREE_MAX_MEMBERS_PER_PROJECT = 19;

    private const FREE_MAX_STORAGE_BYTES_PER_PROJECT = 3 * (1024 ** 3);

    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {}

    public function assertCanCreateProject(Organization $organization): void
    {
        $max = $this->maxProjects($organization);

        if ($max === null) {
            return;
        }

        if ($this->projects->activeCountForOrganization($organization) >= $max) {
            throw new QuotaExceededException(
                'project_limit',
                "無料プランで作成できるプロジェクトは{$max}件までです。有料プランにアップグレードしてください。",
            );
        }
    }

    public function assertCanAddMember(Project $project): void
    {
        $max = $this->maxMembersPerProject($project->organization);

        if ($max === null) {
            return;
        }

        if ($this->projects->memberCount($project) >= $max) {
            throw new QuotaExceededException(
                'member_limit',
                'このプランではプロジェクトのメンバー数が上限に達しています。有料プランにアップグレードしてください。',
            );
        }
    }

    public function assertCanUpload(Project $project, int $additionalBytes, int $currentBytes): void
    {
        $max = $this->maxStorageBytesPerProject($project->organization);

        if ($max === null) {
            return;
        }

        if ($currentBytes + $additionalBytes > $max) {
            throw new QuotaExceededException(
                'storage_limit',
                'このプランではプロジェクトのストレージ容量が上限に達しています。有料プランにアップグレードしてください。',
            );
        }
    }

    private function maxProjects(Organization $organization): ?int
    {
        return $organization->plan ? $organization->plan->max_projects : self::FREE_MAX_PROJECTS;
    }

    private function maxMembersPerProject(Organization $organization): ?int
    {
        return $organization->plan ? $organization->plan->max_members_per_project : self::FREE_MAX_MEMBERS_PER_PROJECT;
    }

    private function maxStorageBytesPerProject(Organization $organization): ?int
    {
        return $organization->plan ? $organization->plan->max_storage_bytes_per_project : self::FREE_MAX_STORAGE_BYTES_PER_PROJECT;
    }
}

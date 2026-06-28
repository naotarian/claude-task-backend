<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $this->isProjectMember($user, $project) || $this->isOrgManager($user, $project);
    }

    public function manage(User $user, Project $project): bool
    {
        // Project role takes precedence; the owning org's admins/owners may
        // also manage their organization's projects.
        if ($project->memberRole($user)?->canManage()) {
            return true;
        }

        return $this->isOrgManager($user, $project);
    }

    public function addMembers(User $user, Project $project): bool
    {
        return $this->manage($user, $project);
    }

    /**
     * Whether the user may create/edit content (tasks, comments) in the project.
     * Project admins and members can; viewers cannot. Org admins/owners also can.
     */
    public function contribute(User $user, Project $project): bool
    {
        if ($project->memberRole($user)?->canEdit()) {
            return true;
        }

        return $this->isOrgManager($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->manage($user, $project);
    }

    private function isProjectMember(User $user, Project $project): bool
    {
        return $project->memberRole($user) !== null;
    }

    private function isOrgManager(User $user, Project $project): bool
    {
        return $user->roleIn($project->organization)?->canManage() ?? false;
    }
}

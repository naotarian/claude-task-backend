<?php

namespace App\UseCases\Project;

use App\Enums\ProjectRole;
use App\Models\ProjectMember;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;

class UpdateProjectMemberUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {}

    public function handle(ProjectMember $member, ProjectRole $role): ProjectMember
    {
        if ($member->role === ProjectRole::Owner
            && $role !== ProjectRole::Owner
            && $this->projects->ownerCount($member->project) <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => ['プロジェクトには最低1人のオーナーが必要です。'],
            ]);
        }

        return $this->transaction(function () use ($member, $role): ProjectMember {
            $member->role = $role;
            $member->save();

            return $member->load('user');
        });
    }
}

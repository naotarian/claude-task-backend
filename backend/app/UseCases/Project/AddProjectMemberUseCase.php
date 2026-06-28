<?php

namespace App\UseCases\Project;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\QuotaService;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;

/**
 * Adds a user to a project. The user may belong to a different organization
 * (or be a freelancer) — project membership is cross-organization.
 */
class AddProjectMemberUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly UserRepositoryInterface $users,
        private readonly QuotaService $quota,
    ) {}

    public function handle(Project $project, string $email, ProjectRole $role): ProjectMember
    {
        $this->quota->assertCanAddMember($project);

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['このメールアドレスのユーザーは登録されていません。'],
            ]);
        }

        if ($this->projects->isMember($project, $user)) {
            throw ValidationException::withMessages([
                'email' => ['このユーザーは既にプロジェクトのメンバーです。'],
            ]);
        }

        return $this->transaction(fn (): ProjectMember => $this->projects->addMember($project, $user, $role));
    }
}

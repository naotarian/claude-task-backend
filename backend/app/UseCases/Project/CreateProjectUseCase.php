<?php

namespace App\UseCases\Project;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\DefaultProjectStatuses;
use App\Services\QuotaService;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;

class CreateProjectUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly DefaultProjectStatuses $defaultStatuses,
        private readonly QuotaService $quota,
    ) {}

    public function handle(Organization $organization, User $creator, string $key, string $name, ?string $description): Project
    {
        $this->quota->assertCanCreateProject($organization);

        if ($this->projects->keyExists($organization, $key)) {
            throw ValidationException::withMessages([
                'key' => ['このプロジェクトキーは既に使われています。'],
            ]);
        }

        return $this->transaction(function () use ($organization, $creator, $key, $name, $description): Project {
            $project = $this->projects->create([
                'organization_id' => $organization->id,
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'status' => ProjectStatus::Active,
                'categories_enabled' => true,
                'task_sequence' => 0,
            ]);

            $this->defaultStatuses->seed($project);
            $this->projects->addMember($project, $creator, ProjectRole::Owner);

            return $project;
        });
    }
}

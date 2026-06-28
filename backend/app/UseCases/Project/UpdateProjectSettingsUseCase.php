<?php

namespace App\UseCases\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\UseCases\AbstractUseCase;

class UpdateProjectSettingsUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  e.g. ['categories_enabled' => bool]
     */
    public function handle(Project $project, array $attributes): Project
    {
        return $this->transaction(fn (): Project => $this->projects->update($project, $attributes));
    }
}

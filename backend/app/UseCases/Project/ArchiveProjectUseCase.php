<?php

namespace App\UseCases\Project;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\UseCases\AbstractUseCase;

class ArchiveProjectUseCase extends AbstractUseCase
{
    public function handle(Project $project, bool $archived): Project
    {
        return $this->transaction(function () use ($project, $archived): Project {
            $project->status = $archived ? ProjectStatus::Archived : ProjectStatus::Active;
            $project->save();

            return $project;
        });
    }
}

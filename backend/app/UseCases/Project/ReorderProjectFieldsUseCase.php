<?php

namespace App\UseCases\Project;

use App\Models\Project;
use App\Repositories\Contracts\ProjectFieldRepositoryInterface;
use App\UseCases\AbstractUseCase;

class ReorderProjectFieldsUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectFieldRepositoryInterface $fields,
    ) {}

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function handle(Project $project, array $orderedIds): void
    {
        $this->transaction(fn () => $this->fields->reorder($project, $orderedIds));
    }
}

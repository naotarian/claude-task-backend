<?php

namespace App\UseCases\Project;

use App\Models\ProjectField;
use App\Repositories\Contracts\ProjectFieldRepositoryInterface;
use App\UseCases\AbstractUseCase;

class DeleteProjectFieldUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectFieldRepositoryInterface $fields,
    ) {}

    public function handle(ProjectField $field): void
    {
        // Options and task values are removed via FK cascade.
        $this->transaction(fn () => $this->fields->delete($field));
    }
}

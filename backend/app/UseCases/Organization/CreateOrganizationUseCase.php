<?php

namespace App\UseCases\Organization;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\UseCases\AbstractUseCase;

class CreateOrganizationUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly OrganizationProvisioner $provisioner,
    ) {}

    public function handle(User $owner, string $name): Organization
    {
        return $this->transaction(fn (): Organization => $this->provisioner->provision(
            name: $name,
            type: OrganizationType::Organization,
            owner: $owner,
        ));
    }
}

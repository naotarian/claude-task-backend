<?php

namespace App\Support;

use App\Models\Organization;
use RuntimeException;

/**
 * Request-scoped holder for the "current organization" (tenant).
 *
 * Set by the SetCurrentOrganization middleware and consumed by use cases,
 * services and repositories that need to scope queries to the active tenant.
 */
class CurrentOrganization
{
    private ?Organization $organization = null;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): Organization
    {
        if ($this->organization === null) {
            throw new RuntimeException('No current organization has been resolved for this request.');
        }

        return $this->organization;
    }

    public function id(): int
    {
        return $this->get()->id;
    }

    public function has(): bool
    {
        return $this->organization !== null;
    }
}

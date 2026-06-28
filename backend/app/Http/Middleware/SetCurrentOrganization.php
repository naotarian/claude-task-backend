<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization for the request and verifies the
 * authenticated user belongs to it. The organization is taken from the
 * route parameter {organization} if present, otherwise from the
 * X-Organization-Id header.
 */
class SetCurrentOrganization
{
    public function __construct(
        private readonly CurrentOrganization $current,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->resolveOrganization($request);

        if ($organization === null) {
            abort(400, 'Organization not specified.');
        }

        $user = $request->user();

        if ($user === null || ! $user->isMemberOf($organization)) {
            abort(403, 'You do not belong to this organization.');
        }

        $this->current->set($organization);

        return $next($request);
    }

    private function resolveOrganization(Request $request): ?Organization
    {
        $routeOrg = $request->route('organization');

        if ($routeOrg instanceof Organization) {
            return $routeOrg;
        }

        $headerId = $request->header('X-Organization-Id');

        if ($headerId !== null) {
            return Organization::query()->find($headerId);
        }

        return null;
    }
}

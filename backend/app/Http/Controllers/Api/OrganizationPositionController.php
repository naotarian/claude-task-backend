<?php

namespace App\Http\Controllers\Api;

use App\Data\OrganizationPositionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StorePositionRequest;
use App\Models\Organization;
use App\Models\OrganizationPosition;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

class OrganizationPositionController extends Controller
{
    public function index(Request $request, Organization $organization, OrganizationRepositoryInterface $organizations): DataCollection
    {
        $this->authorize('view', $organization);

        $positions = $organizations->positionsForOrganization($organization)
            ->map(fn (OrganizationPosition $p) => OrganizationPositionData::fromModel($p))
            ->all();

        return new DataCollection(OrganizationPositionData::class, $positions);
    }

    public function store(StorePositionRequest $request, Organization $organization, OrganizationRepositoryInterface $organizations): JsonResponse
    {
        $this->authorize('manage', $organization);

        $position = $organizations->createPosition($organization, $request->string('name')->toString());

        return OrganizationPositionData::fromModel($position)
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function destroy(Organization $organization, OrganizationPosition $position, OrganizationRepositoryInterface $organizations): Response
    {
        $this->authorize('manage', $organization);
        abort_unless($position->organization_id === $organization->id, 404);

        $organizations->deletePosition($position);

        return response()->noContent();
    }
}

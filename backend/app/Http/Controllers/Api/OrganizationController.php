<?php

namespace App\Http\Controllers\Api;

use App\Data\OrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\UseCases\Organization\CreateOrganizationUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class OrganizationController extends Controller
{
    public function index(Request $request, OrganizationRepositoryInterface $organizations): DataCollection
    {
        $user = $request->user();

        $data = $organizations->forUser($user)
            ->map(fn (Organization $org) => OrganizationData::fromModel($org, $user->roleIn($org)))
            ->all();

        return new DataCollection(OrganizationData::class, $data);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganizationUseCase $useCase): JsonResponse
    {
        $organization = $useCase->handle(
            owner: $request->user(),
            name: $request->string('name')->toString(),
        );

        return OrganizationData::fromModel($organization, $request->user()->roleIn($organization))
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization): OrganizationData
    {
        $this->authorize('view', $organization);

        return OrganizationData::fromModel($organization, $request->user()->roleIn($organization));
    }
}

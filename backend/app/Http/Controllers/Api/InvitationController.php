<?php

namespace App\Http\Controllers\Api;

use App\Data\OrganizationData;
use App\Http\Controllers\Controller;
use App\UseCases\Organization\AcceptInvitationUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function accept(Request $request, string $token, AcceptInvitationUseCase $useCase): JsonResponse
    {
        $organization = $useCase->handle($token, $request->user());

        return OrganizationData::fromModel($organization, $request->user()->roleIn($organization))
            ->toResponse($request)
            ->setStatusCode(200);
    }
}

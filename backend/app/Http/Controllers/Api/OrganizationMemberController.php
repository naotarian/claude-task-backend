<?php

namespace App\Http\Controllers\Api;

use App\Data\OrganizationInvitationData;
use App\Data\OrganizationMemberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteMemberRequest;
use App\Http\Requests\Organization\UpdateMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\UseCases\Organization\InviteMemberUseCase;
use App\UseCases\Organization\UpdateOrganizationMemberUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class OrganizationMemberController extends Controller
{
    public function index(Request $request, Organization $organization): DataCollection
    {
        $this->authorize('view', $organization);

        $members = $organization->members()->with(['user', 'position'])->get()
            ->map(fn ($member) => OrganizationMemberData::fromModel($member))
            ->all();

        return new DataCollection(OrganizationMemberData::class, $members);
    }

    public function update(
        UpdateMemberRequest $request,
        Organization $organization,
        OrganizationMember $member,
        UpdateOrganizationMemberUseCase $useCase,
    ): JsonResponse {
        $this->authorize('manageOwners', $organization);
        abort_unless($member->organization_id === $organization->id, 404);

        $updated = $useCase->handle($member, $request->changes());

        return OrganizationMemberData::fromModel($updated)
            ->toResponse($request)
            ->setStatusCode(200);
    }

    public function invite(InviteMemberRequest $request, Organization $organization, InviteMemberUseCase $useCase): JsonResponse
    {
        $this->authorize('inviteMembers', $organization);

        $invitation = $useCase->handle(
            organization: $organization,
            inviter: $request->user(),
            email: $request->string('email')->toString(),
            role: $request->role(),
        );

        return OrganizationInvitationData::fromModel($invitation)
            ->toResponse($request)
            ->setStatusCode(201);
    }
}

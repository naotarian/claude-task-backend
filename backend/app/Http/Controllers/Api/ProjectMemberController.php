<?php

namespace App\Http\Controllers\Api;

use App\Data\ProjectMemberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AddProjectMemberRequest;
use App\Http\Requests\Project\UpdateProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use App\UseCases\Project\AddProjectMemberUseCase;
use App\UseCases\Project\UpdateProjectMemberUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): DataCollection
    {
        $this->authorize('view', $project);

        $members = $project->members()->with('user')->get()
            ->map(fn ($member) => ProjectMemberData::fromModel($member))
            ->all();

        return new DataCollection(ProjectMemberData::class, $members);
    }

    public function store(AddProjectMemberRequest $request, Project $project, AddProjectMemberUseCase $useCase): JsonResponse
    {
        $this->authorize('addMembers', $project);

        $member = $useCase->handle(
            project: $project,
            email: $request->string('email')->toString(),
            role: $request->role(),
        );

        $member->load('user');

        return ProjectMemberData::fromModel($member)
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function update(
        UpdateProjectMemberRequest $request,
        Project $project,
        ProjectMember $member,
        UpdateProjectMemberUseCase $useCase,
    ): JsonResponse {
        $this->authorize('manage', $project);
        abort_unless($member->project_id === $project->id, 404);

        $updated = $useCase->handle($member, $request->role());

        return ProjectMemberData::fromModel($updated)
            ->toResponse($request)
            ->setStatusCode(200);
    }
}

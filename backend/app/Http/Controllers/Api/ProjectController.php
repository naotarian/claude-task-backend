<?php

namespace App\Http\Controllers\Api;

use App\Data\ProjectData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectSettingsRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\UseCases\Project\ArchiveProjectUseCase;
use App\UseCases\Project\CreateProjectUseCase;
use App\UseCases\Project\UpdateProjectSettingsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class ProjectController extends Controller
{
    public function index(Request $request, Organization $organization, ProjectRepositoryInterface $projects): DataCollection
    {
        // Organization membership is enforced by the `organization` middleware.
        $user = $request->user();
        $includeArchived = $request->boolean('include_archived');

        $data = $projects->forOrganization($organization, $includeArchived)
            ->map(fn (Project $project) => ProjectData::fromModel($project, $project->memberRole($user)))
            ->all();

        return new DataCollection(ProjectData::class, $data);
    }

    public function store(StoreProjectRequest $request, Organization $organization, CreateProjectUseCase $useCase): JsonResponse
    {
        $user = $request->user();

        $project = $useCase->handle(
            organization: $organization,
            creator: $user,
            key: $request->string('key')->toString(),
            name: $request->string('name')->toString(),
            description: $request->input('description'),
        );

        return ProjectData::fromModel($project, $project->memberRole($user), withStatuses: true)
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function show(Request $request, Project $project): ProjectData
    {
        $this->authorize('view', $project);

        $project->load(['statuses', 'fields.options', 'categories']);

        return ProjectData::fromModel($project, $project->memberRole($request->user()), withStatuses: true);
    }

    public function updateSettings(UpdateProjectSettingsRequest $request, Project $project, UpdateProjectSettingsUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $updated = $useCase->handle($project, $request->payload());
        $updated->load(['statuses', 'fields.options', 'categories']);

        return ProjectData::fromModel($updated, $project->memberRole($request->user()), withStatuses: true)
            ->toResponse($request)
            ->setStatusCode(200);
    }

    public function archive(Request $request, Project $project, ArchiveProjectUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $updated = $useCase->handle($project, archived: true);

        return ProjectData::fromModel($updated, $project->memberRole($request->user()))
            ->toResponse($request)
            ->setStatusCode(200);
    }

    public function unarchive(Request $request, Project $project, ArchiveProjectUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $updated = $useCase->handle($project, archived: false);

        return ProjectData::fromModel($updated, $project->memberRole($request->user()))
            ->toResponse($request)
            ->setStatusCode(200);
    }
}

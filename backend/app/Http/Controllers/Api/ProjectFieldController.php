<?php

namespace App\Http\Controllers\Api;

use App\Data\ProjectFieldData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ReorderProjectFieldsRequest;
use App\Http\Requests\Project\StoreProjectFieldRequest;
use App\Http\Requests\Project\UpdateProjectFieldRequest;
use App\Models\Project;
use App\Models\ProjectField;
use App\Repositories\Contracts\ProjectFieldRepositoryInterface;
use App\UseCases\Project\CreateProjectFieldUseCase;
use App\UseCases\Project\DeleteProjectFieldUseCase;
use App\UseCases\Project\ReorderProjectFieldsUseCase;
use App\UseCases\Project\UpdateProjectFieldUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;

class ProjectFieldController extends Controller
{
    public function index(Project $project, ProjectFieldRepositoryInterface $fields): DataCollection
    {
        // Managers see all fields (including hidden) for configuration.
        $this->authorize('manage', $project);

        $data = $fields->forProject($project)
            ->map(fn (ProjectField $f) => ProjectFieldData::fromModel($f))
            ->all();

        return new DataCollection(ProjectFieldData::class, $data);
    }

    public function store(StoreProjectFieldRequest $request, Project $project, CreateProjectFieldUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $field = $useCase->handle(
            project: $project,
            name: $request->string('name')->toString(),
            type: $request->fieldType(),
            options: $request->options(),
        );

        return ProjectFieldData::fromModel($field)->toResponse($request)->setStatusCode(201);
    }

    public function update(UpdateProjectFieldRequest $request, Project $project, ProjectField $field, UpdateProjectFieldUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);
        abort_unless($field->project_id === $project->id, 404);

        $updated = $useCase->handle($field, $request->attributesToUpdate(), $request->options());

        return ProjectFieldData::fromModel($updated)->toResponse($request)->setStatusCode(200);
    }

    public function destroy(Project $project, ProjectField $field, DeleteProjectFieldUseCase $useCase): Response
    {
        $this->authorize('manage', $project);
        abort_unless($field->project_id === $project->id, 404);

        $useCase->handle($field);

        return response()->noContent();
    }

    public function reorder(ReorderProjectFieldsRequest $request, Project $project, ReorderProjectFieldsUseCase $useCase): JsonResponse
    {
        $this->authorize('manage', $project);

        $useCase->handle($project, $request->ids());

        return response()->json(['message' => 'reordered']);
    }
}

<?php

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

/**
 * @return array{0: Project, 1: User}
 */
function projectWithOwner(): array
{
    $org = Organization::factory()->create();
    $owner = User::factory()->create();
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => OrganizationRole::Member,
    ]);
    $project = Project::factory()->create(['organization_id' => $org->id]);
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);

    return [$project, $owner];
}

it('プロジェクトオーナーはメンバーの権限を変更できる', function () {
    [$project, $owner] = projectWithOwner();
    $member = ProjectMember::factory()->role(ProjectRole::Viewer)->create(['project_id' => $project->id]);

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/members/{$member->id}", ['role' => 'editor'])
        ->assertOk()
        ->assertJsonPath('role', 'editor');
});

it('プロジェクトの最後のオーナーの降格を防ぐ', function () {
    [$project, $owner] = projectWithOwner();
    $ownerMember = ProjectMember::where('project_id', $project->id)->where('user_id', $owner->id)->first();

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/members/{$ownerMember->id}", ['role' => 'editor'])
        ->assertStatus(422);
});

it('編集者はメンバーの権限を変更できない', function () {
    [$project] = projectWithOwner();
    $editor = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Editor)->create([
        'project_id' => $project->id,
        'user_id' => $editor->id,
    ]);
    $other = ProjectMember::factory()->role(ProjectRole::Viewer)->create(['project_id' => $project->id]);

    $this->actingAs($editor)
        ->patchJson("/api/projects/{$project->id}/members/{$other->id}", ['role' => 'editor'])
        ->assertForbidden();
});

it('オーナーはプロジェクトをアーカイブ/解除でき一覧が絞り込まれる', function () {
    [$project, $owner] = projectWithOwner();
    $org = $project->organization; // owner is already an org member (for listing)

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/archive")
        ->assertOk()
        ->assertJsonPath('status', 'archived');

    // Default org listing excludes archived
    $this->actingAs($owner)
        ->getJson("/api/organizations/{$org->id}/projects")
        ->assertOk()
        ->assertJsonCount(0);

    // include_archived shows it
    $this->actingAs($owner)
        ->getJson("/api/organizations/{$org->id}/projects?include_archived=1")
        ->assertOk()
        ->assertJsonCount(1);

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/unarchive")
        ->assertOk()
        ->assertJsonPath('status', 'active');
});

it('編集者はプロジェクトをアーカイブできない', function () {
    [$project] = projectWithOwner();
    $editor = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Editor)->create([
        'project_id' => $project->id,
        'user_id' => $editor->id,
    ]);

    $this->actingAs($editor)
        ->postJson("/api/projects/{$project->id}/archive")
        ->assertForbidden();
});

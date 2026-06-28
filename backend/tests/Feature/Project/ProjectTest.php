<?php

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

/**
 * @return array{0: Organization, 1: User}
 */
function orgWithMember(OrganizationRole $role = OrganizationRole::Owner): array
{
    $user = User::factory()->create();
    $org = Organization::factory()->create(['owner_user_id' => $user->id]);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => $role,
    ]);

    return [$org, $user];
}

it('デフォルトステータス付きでプロジェクトを作成し作成者がオーナーになる', function () {
    [$org, $user] = orgWithMember();

    $response = $this->actingAs($user)
        ->postJson("/api/organizations/{$org->id}/projects", [
            'key' => 'PROJ',
            'name' => '新規プロジェクト',
            'description' => '説明',
        ])
        ->assertCreated()
        ->assertJsonPath('key', 'PROJ')
        ->assertJsonPath('role', 'owner');

    $projectId = $response->json('id');

    // 5 default statuses seeded (未割当 + 未対応/処理中/処理済み/完了)
    $this->assertDatabaseCount('task_statuses', 5);
    expect($response->json('statuses'))->toHaveCount(5);

    $this->assertDatabaseHas('project_members', [
        'project_id' => $projectId,
        'user_id' => $user->id,
        'role' => ProjectRole::Owner->value,
    ]);
});

it('同一組織内の重複プロジェクトキーを拒否する', function () {
    [$org, $user] = orgWithMember();
    Project::factory()->create(['organization_id' => $org->id, 'key' => 'PROJ']);

    $this->actingAs($user)
        ->postJson("/api/organizations/{$org->id}/projects", [
            'key' => 'PROJ',
            'name' => 'Dup',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('key');
});

it('プロジェクトキーの形式を検証する', function () {
    [$org, $user] = orgWithMember();

    $this->actingAs($user)
        ->postJson("/api/organizations/{$org->id}/projects", [
            'key' => 'lower case',
            'name' => 'X',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('key');
});

it('組織の非メンバーはプロジェクトを作成できない', function () {
    [$org] = orgWithMember();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->postJson("/api/organizations/{$org->id}/projects", ['key' => 'PROJ', 'name' => 'X'])
        ->assertForbidden();
});

it('組織内のプロジェクト一覧を取得できる', function () {
    [$org, $user] = orgWithMember();
    Project::factory()->count(2)->create(['organization_id' => $org->id]);

    $this->actingAs($user)
        ->getJson("/api/organizations/{$org->id}/projects")
        ->assertOk()
        ->assertJsonCount(2);
});

it('プロジェクトメンバーはステータス付きでプロジェクトを閲覧できる', function () {
    [$org, $user] = orgWithMember();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    ProjectMember::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'role' => ProjectRole::Editor,
    ]);

    $this->actingAs($user)
        ->getJson("/api/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('id', $project->id)
        ->assertJsonPath('role', 'editor');
});

it('無関係のユーザーはプロジェクトを閲覧できない', function () {
    $project = Project::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}")
        ->assertForbidden();
});

it('組織管理者はプロジェクト未参加でも閲覧できる', function () {
    [$org, $admin] = orgWithMember(OrganizationRole::Admin);
    $project = Project::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($admin)
        ->getJson("/api/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('role', null);
});

it('組織をまたいでプロジェクトにメンバーを追加できる', function () {
    [$org, $admin] = orgWithMember();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $admin->id,
    ]);

    // A freelancer / member of another organization
    $outsider = User::factory()->create(['email' => 'freelancer@example.com']);

    $this->actingAs($admin)
        ->postJson("/api/projects/{$project->id}/members", [
            'email' => 'freelancer@example.com',
            'role' => 'editor',
        ])
        ->assertCreated()
        ->assertJsonPath('email', 'freelancer@example.com')
        ->assertJsonPath('role', 'editor');

    $this->assertDatabaseHas('project_members', [
        'project_id' => $project->id,
        'user_id' => $outsider->id,
    ]);
});

it('未登録メールのプロジェクト追加を拒否する', function () {
    [$org, $admin] = orgWithMember();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->postJson("/api/projects/{$project->id}/members", [
            'email' => 'ghost@example.com',
            'role' => 'editor',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('email');
});

it('プロジェクトの閲覧者はメンバーを追加できない', function () {
    $project = Project::factory()->create();
    $viewer = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Viewer)->create([
        'project_id' => $project->id,
        'user_id' => $viewer->id,
    ]);
    $target = User::factory()->create(['email' => 'target@example.com']);

    $this->actingAs($viewer)
        ->postJson("/api/projects/{$project->id}/members", [
            'email' => 'target@example.com',
            'role' => 'editor',
        ])
        ->assertForbidden();
});

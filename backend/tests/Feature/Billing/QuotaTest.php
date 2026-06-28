<?php

use App\Enums\OrganizationRole;
use App\Enums\ProjectRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{0: Organization, 1: User}
 */
function freeOrgOwner(): array
{
    $owner = User::factory()->create();
    $org = Organization::factory()->create(['owner_user_id' => $owner->id, 'plan_id' => null]);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => OrganizationRole::Owner,
    ]);

    return [$org, $owner];
}

it('無料プランは3件までプロジェクトを作成でき4件目はブロックされる', function () {
    [$org, $owner] = freeOrgOwner();
    Project::factory()->count(3)->create(['organization_id' => $org->id]);

    $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/projects", ['key' => 'NEW', 'name' => '4つ目'])
        ->assertStatus(402)
        ->assertJsonPath('quota', 'project_limit');
});

it('有料プランでは4件以上のプロジェクトを作成できる', function () {
    $plan = Plan::factory()->paid()->create();
    [$org, $owner] = freeOrgOwner();
    $org->update(['plan_id' => $plan->id]);
    Project::factory()->count(3)->create(['organization_id' => $org->id]);

    $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/projects", ['key' => 'NEW', 'name' => '4つ目'])
        ->assertCreated();
});

it('無料プランではプロジェクトに20人目のメンバーを追加できない', function () {
    [$org, $owner] = freeOrgOwner();
    $project = Project::factory()->create(['organization_id' => $org->id]);

    // 19 existing members → adding one more would reach 20 (paid only).
    ProjectMember::factory()->count(19)->create(['project_id' => $project->id]);
    User::factory()->create(['email' => 'newcomer@example.com']);

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/members", [
            'email' => 'newcomer@example.com',
            'role' => 'editor',
        ])
        ->assertStatus(402)
        ->assertJsonPath('quota', 'member_limit');
});

it('添付をアップロードしサイズを集計する', function () {
    Storage::fake('s3');
    [$org, $owner] = freeOrgOwner();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);
    $status = TaskStatus::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $status->id,
        'seq_number' => 1,
    ]);

    $file = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'); // 100 KB

    $this->actingAs($owner)
        ->postJson("/api/tasks/{$task->id}/attachments", ['file' => $file])
        ->assertCreated()
        ->assertJsonPath('originalName', 'spec.pdf');

    $this->assertDatabaseHas('task_attachments', [
        'task_id' => $task->id,
        'project_id' => $project->id,
        'original_name' => 'spec.pdf',
    ]);

    $attachment = TaskAttachment::first();
    Storage::disk('s3')->assertExists($attachment->path);
    expect($attachment->size_bytes)->toBeGreaterThan(0);
});

it('無料プランのストレージ上限を超えるアップロードはブロックされる', function () {
    Storage::fake('s3');
    [$org, $owner] = freeOrgOwner();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);
    $status = TaskStatus::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $status->id,
        'seq_number' => 1,
    ]);

    // Existing usage already at 3GB.
    TaskAttachment::factory()->create([
        'task_id' => $task->id,
        'project_id' => $project->id,
        'size_bytes' => 3 * (1024 ** 3),
    ]);

    $file = UploadedFile::fake()->create('more.pdf', 10);

    $this->actingAs($owner)
        ->postJson("/api/tasks/{$task->id}/attachments", ['file' => $file])
        ->assertStatus(402)
        ->assertJsonPath('quota', 'storage_limit');
});

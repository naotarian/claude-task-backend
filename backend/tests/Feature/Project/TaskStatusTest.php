<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;

/**
 * @return array{0: Project, 1: User}
 */
function projectOwnerForStatus(): array
{
    $project = Project::factory()->create();
    $owner = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);
    // protected "未割当" bucket
    TaskStatus::factory()->create([
        'project_id' => $project->id,
        'name' => '未割当',
        'position' => 0,
        'is_protected' => true,
    ]);

    return [$project, $owner];
}

it('ステータスを末尾に追加して作成できる', function () {
    [$project, $owner] = projectOwnerForStatus();

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/statuses", ['name' => 'レビュー中', 'color' => '#ff0000'])
        ->assertCreated()
        ->assertJsonPath('name', 'レビュー中')
        ->assertJsonPath('isProtected', false);

    $this->assertDatabaseHas('task_statuses', ['project_id' => $project->id, 'name' => 'レビュー中']);
});

it('ステータスを更新できる（改名・非表示）', function () {
    [$project, $owner] = projectOwnerForStatus();
    $status = TaskStatus::factory()->create(['project_id' => $project->id, 'name' => '古い名前', 'position' => 1]);

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/statuses/{$status->id}", ['name' => '新しい名前', 'is_hidden' => true])
        ->assertOk()
        ->assertJsonPath('name', '新しい名前')
        ->assertJsonPath('isHidden', true);
});

it('ステータスを削除しタスクを「未割当」へ移動する', function () {
    [$project, $owner] = projectOwnerForStatus();
    $unassigned = TaskStatus::where('project_id', $project->id)->where('is_protected', true)->first();
    $status = TaskStatus::factory()->create(['project_id' => $project->id, 'position' => 1]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $status->id,
        'seq_number' => 1,
    ]);

    $this->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/statuses/{$status->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('task_statuses', ['id' => $status->id]);
    expect($task->fresh()->task_status_id)->toBe($unassigned->id);
});

it('保護された「未割当」ステータスは削除できない', function () {
    [$project, $owner] = projectOwnerForStatus();
    $unassigned = TaskStatus::where('project_id', $project->id)->where('is_protected', true)->first();

    $this->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/statuses/{$unassigned->id}")
        ->assertStatus(422);

    $this->assertDatabaseHas('task_statuses', ['id' => $unassigned->id]);
});

it('ステータスを並べ替えられる', function () {
    [$project, $owner] = projectOwnerForStatus();
    $a = TaskStatus::factory()->create(['project_id' => $project->id, 'position' => 1]);
    $b = TaskStatus::factory()->create(['project_id' => $project->id, 'position' => 2]);

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/statuses/reorder", ['ids' => [$b->id, $a->id]])
        ->assertOk();

    expect($b->fresh()->position)->toBe(0);
    expect($a->fresh()->position)->toBe(1);
});

it('編集者はステータスを管理できない', function () {
    [$project] = projectOwnerForStatus();
    $editor = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Editor)->create([
        'project_id' => $project->id,
        'user_id' => $editor->id,
    ]);

    $this->actingAs($editor)
        ->postJson("/api/projects/{$project->id}/statuses", ['name' => 'X'])
        ->assertForbidden();
});

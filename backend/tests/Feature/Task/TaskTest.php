<?php

use App\Enums\ProjectRole;
use App\Enums\TaskStatusCategory;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{0: Project, 1: User, 2: TaskStatus}
 */
function projectWithRole(ProjectRole $role = ProjectRole::Owner): array
{
    $project = Project::factory()->create();
    $user = User::factory()->create();
    ProjectMember::factory()->role($role)->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);
    $todo = TaskStatus::factory()->create([
        'project_id' => $project->id,
        'name' => '未対応',
        'category' => TaskStatusCategory::Todo,
        'position' => 0,
    ]);
    TaskStatus::factory()->create([
        'project_id' => $project->id,
        'name' => '完了',
        'category' => TaskStatusCategory::Done,
        'position' => 1,
    ]);

    return [$project, $user, $todo];
}

it('デフォルトステータスと連番でタスクを作成できる', function () {
    [$project, $user, $todo] = projectWithRole();

    $first = $this->actingAs($user)
        ->postJson("/api/projects/{$project->id}/tasks", ['title' => '最初のタスク'])
        ->assertCreated()
        ->assertJsonPath('seqNumber', 1)
        ->assertJsonPath('taskStatusId', $todo->id)
        ->json();

    $this->actingAs($user)
        ->postJson("/api/projects/{$project->id}/tasks", ['title' => '2番目'])
        ->assertCreated()
        ->assertJsonPath('seqNumber', 2);

    expect($first['title'])->toBe('最初のタスク');
});

it('日程と予定工数（予実）付きでタスクを作成できる', function () {
    [$project, $user] = projectWithRole();

    $this->actingAs($user)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => '予実タスク',
            'due_date' => '2026-07-31',
            'planned_start_date' => '2026-07-01',
            'planned_end_date' => '2026-07-15',
            'estimated_hours' => 12.5,
            'priority' => 'high',
            'progress' => 30,
        ])
        ->assertCreated()
        ->assertJsonPath('dueDate', '2026-07-31')
        ->assertJsonPath('plannedStartDate', '2026-07-01')
        ->assertJsonPath('estimatedHours', 12.5)
        ->assertJsonPath('priority', 'high')
        ->assertJsonPath('progress', 30)
        ->assertJsonPath('actualHours', 0);
});

it('プロジェクトメンバーをタスクに割り当てられる', function () {
    [$project, $admin] = projectWithRole();
    $assignee = User::factory()->create();
    ProjectMember::factory()->create(['project_id' => $project->id, 'user_id' => $assignee->id]);

    $this->actingAs($admin)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => '担当付き',
            'assignee_user_ids' => [$assignee->id],
        ])
        ->assertCreated()
        ->assertJsonPath('assignees.0.id', $assignee->id)
        ->assertJsonPath('assignees.0.name', $assignee->name);
});

it('プロジェクト非メンバーへの割り当てを拒否する', function () {
    [$project, $admin] = projectWithRole();
    $outsider = User::factory()->create();

    $this->actingAs($admin)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'X',
            'assignee_user_ids' => [$outsider->id],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('assignee_user_ids.0');
});

it('閲覧者はタスクを作成できない', function () {
    [$project] = projectWithRole();
    $viewer = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Viewer)->create([
        'project_id' => $project->id,
        'user_id' => $viewer->id,
    ]);

    $this->actingAs($viewer)
        ->postJson("/api/projects/{$project->id}/tasks", ['title' => 'X'])
        ->assertForbidden();
});

it('タスクを一覧取得しステータスで絞り込める', function () {
    [$project, $user, $todo] = projectWithRole();
    $done = TaskStatus::where('project_id', $project->id)->where('name', '完了')->first();

    Task::factory()->create(['project_id' => $project->id, 'task_status_id' => $todo->id, 'seq_number' => 1]);
    Task::factory()->create(['project_id' => $project->id, 'task_status_id' => $done->id, 'seq_number' => 2]);

    $this->actingAs($user)
        ->getJson("/api/projects/{$project->id}/tasks")
        ->assertOk()
        ->assertJsonCount(2);

    $this->actingAs($user)
        ->getJson("/api/projects/{$project->id}/tasks?status_id={$done->id}")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.taskStatusId', $done->id);
});

it('タスクを更新できる（ステータス・進捗・実績日程）', function () {
    [$project, $user, $todo] = projectWithRole();
    $done = TaskStatus::where('project_id', $project->id)->where('name', '完了')->first();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $todo->id,
        'seq_number' => 1,
    ]);

    $this->actingAs($user)
        ->patchJson("/api/tasks/{$task->id}", [
            'task_status_id' => $done->id,
            'progress' => 100,
            'actual_start_date' => '2026-07-02',
            'actual_end_date' => '2026-07-10',
        ])
        ->assertOk()
        ->assertJsonPath('taskStatusId', $done->id)
        ->assertJsonPath('progress', 100)
        ->assertJsonPath('actualEndDate', '2026-07-10');
});

it('タスクをソフトデリートする', function () {
    [$project, $user, $todo] = projectWithRole();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $todo->id,
        'seq_number' => 1,
    ]);

    $this->actingAs($user)
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

it('タスク削除時に添付（ファイルとレコード）も削除される', function () {
    Storage::fake('s3');
    [$project, $user, $todo] = projectWithRole();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $todo->id,
        'seq_number' => 1,
    ]);

    $file = UploadedFile::fake()->create('a.pdf', 50);
    $this->actingAs($user)->postJson("/api/tasks/{$task->id}/attachments", ['file' => $file])->assertCreated();
    $attachment = TaskAttachment::first();
    Storage::disk('s3')->assertExists($attachment->path);

    $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

    $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
    Storage::disk('s3')->assertMissing($attachment->path);
});

it('タスクに複数の担当者を割り当てられる', function () {
    [$project, $admin] = projectWithRole();
    $a = User::factory()->create();
    $b = User::factory()->create();
    ProjectMember::factory()->create(['project_id' => $project->id, 'user_id' => $a->id]);
    ProjectMember::factory()->create(['project_id' => $project->id, 'user_id' => $b->id]);

    $this->actingAs($admin)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => '複数担当',
            'assignee_user_ids' => [$a->id, $b->id],
        ])
        ->assertCreated()
        ->assertJsonCount(2, 'assignees');
});

it('コメントを追加して一覧取得できる', function () {
    [$project, $user, $todo] = projectWithRole();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $todo->id,
        'seq_number' => 1,
    ]);

    $this->actingAs($user)
        ->postJson("/api/tasks/{$task->id}/comments", ['body' => 'コメントです'])
        ->assertCreated()
        ->assertJsonPath('body', 'コメントです')
        ->assertJsonPath('userName', $user->name);

    $this->actingAs($user)
        ->getJson("/api/tasks/{$task->id}/comments")
        ->assertOk()
        ->assertJsonCount(1);
});

it('工数を記録し実績工数が積み上がる', function () {
    [$project, $user, $todo] = projectWithRole();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_status_id' => $todo->id,
        'seq_number' => 1,
    ]);

    $this->actingAs($user)
        ->postJson("/api/tasks/{$task->id}/work-logs", [
            'worked_on' => '2026-07-01',
            'hours' => 1.5,
        ])
        ->assertCreated()
        ->assertJsonPath('hours', 1.5);

    $this->actingAs($user)
        ->postJson("/api/tasks/{$task->id}/work-logs", [
            'worked_on' => '2026-07-02',
            'hours' => 2,
        ])
        ->assertCreated();

    expect((float) $task->fresh()->actual_hours)->toBe(3.5);

    $this->actingAs($user)
        ->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('actualHours', 3.5);
});

it('非メンバーはタスクを閲覧できない', function () {
    [$project] = projectWithRole();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->getJson("/api/projects/{$project->id}/tasks")
        ->assertForbidden();
});

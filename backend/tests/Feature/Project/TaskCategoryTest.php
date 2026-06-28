<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskStatus;
use App\Models\User;

/**
 * @return array{0: Project, 1: User, 2: TaskStatus}
 */
function projectOwnerForCategory(): array
{
    $project = Project::factory()->create();
    $owner = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Owner)->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);
    $status = TaskStatus::factory()->create(['project_id' => $project->id, 'position' => 0]);

    return [$project, $owner, $status];
}

it('カテゴリの作成・改名・並べ替えができる', function () {
    [$project, $owner] = projectOwnerForCategory();

    $design = $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/categories", ['name' => '設計'])
        ->assertCreated()
        ->assertJsonPath('name', '設計')
        ->json();

    $impl = $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/categories", ['name' => '実装'])
        ->assertCreated()->json();

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/categories/{$design['id']}", ['name' => '基本設計'])
        ->assertOk()->assertJsonPath('name', '基本設計');

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/categories/reorder", ['ids' => [$impl['id'], $design['id']]])
        ->assertOk();

    expect(TaskCategory::find($impl['id'])->position)->toBe(0);
});

it('編集者はカテゴリを管理できない', function () {
    [$project] = projectOwnerForCategory();
    $editor = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Editor)->create(['project_id' => $project->id, 'user_id' => $editor->id]);

    $this->actingAs($editor)
        ->postJson("/api/projects/{$project->id}/categories", ['name' => 'X'])
        ->assertForbidden();
});

it('タスクにカテゴリを設定しカテゴリ削除時は未割り当てになる', function () {
    [$project, $owner, $status] = projectOwnerForCategory();
    $category = TaskCategory::factory()->create(['project_id' => $project->id, 'name' => 'バグ修正']);

    $task = $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => '不具合',
            'task_status_id' => $status->id,
            'task_category_id' => $category->id,
        ])
        ->assertCreated()
        ->assertJsonPath('categoryId', $category->id)
        ->assertJsonPath('categoryName', 'バグ修正')
        ->json();

    $this->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/categories/{$category->id}")
        ->assertNoContent();

    expect(Task::find($task['id'])->task_category_id)->toBeNull();
});

it('他プロジェクトのカテゴリを拒否する', function () {
    [$project, $owner, $status] = projectOwnerForCategory();
    $otherCategory = TaskCategory::factory()->create(); // different project

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'X',
            'task_status_id' => $status->id,
            'task_category_id' => $otherCategory->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('task_category_id');
});

it('カテゴリ機能のON/OFF設定を切り替えられる', function () {
    [$project, $owner] = projectOwnerForCategory();

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/settings", ['categories_enabled' => false])
        ->assertOk()
        ->assertJsonPath('categoriesEnabled', false);

    expect($project->fresh()->categories_enabled)->toBeFalse();
});

it('カテゴリと優先度でタスクを絞り込める', function () {
    [$project, $owner, $status] = projectOwnerForCategory();
    $cat = TaskCategory::factory()->create(['project_id' => $project->id]);
    Task::factory()->create(['project_id' => $project->id, 'task_status_id' => $status->id, 'task_category_id' => $cat->id, 'seq_number' => 1, 'priority' => 'high']);
    Task::factory()->create(['project_id' => $project->id, 'task_status_id' => $status->id, 'seq_number' => 2, 'priority' => 'low']);

    $this->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/tasks?category_id={$cat->id}")
        ->assertOk()
        ->assertJsonCount(1);

    $this->actingAs($owner)
        ->getJson("/api/projects/{$project->id}/tasks?priority=high")
        ->assertOk()
        ->assertJsonCount(1);
});

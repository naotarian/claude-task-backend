<?php

use App\Enums\ProjectFieldType;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\ProjectFieldOption;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskFieldValue;
use App\Models\TaskStatus;
use App\Models\User;

/**
 * @return array{0: Project, 1: User, 2: TaskStatus}
 */
function projectOwnerForField(): array
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

it('テキストのカスタム項目を作成できる', function () {
    [$project, $owner] = projectOwnerForField();

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/fields", ['name' => '本番デプロイ日時', 'type' => 'datetime'])
        ->assertCreated()
        ->assertJsonPath('name', '本番デプロイ日時')
        ->assertJsonPath('type', 'datetime');
});

it('選択肢付きのセレクト項目を作成できる', function () {
    [$project, $owner] = projectOwnerForField();

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/fields", [
            'name' => '環境',
            'type' => 'select',
            'options' => ['dev', 'staging', 'prod'],
        ])
        ->assertCreated()
        ->assertJsonCount(3, 'options');
});

it('選択肢のないセレクト項目を拒否する', function () {
    [$project, $owner] = projectOwnerForField();

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/fields", ['name' => '環境', 'type' => 'select'])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('options');
});

it('編集者はカスタム項目を管理できない', function () {
    [$project] = projectOwnerForField();
    $editor = User::factory()->create();
    ProjectMember::factory()->role(ProjectRole::Editor)->create(['project_id' => $project->id, 'user_id' => $editor->id]);

    $this->actingAs($editor)
        ->postJson("/api/projects/{$project->id}/fields", ['name' => 'X', 'type' => 'text'])
        ->assertForbidden();
});

it('カスタム項目を更新して非表示にできる', function () {
    [$project, $owner] = projectOwnerForField();
    $field = ProjectField::factory()->create(['project_id' => $project->id, 'name' => '旧']);

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/fields/{$field->id}", ['name' => '新', 'is_hidden' => true])
        ->assertOk()
        ->assertJsonPath('name', '新')
        ->assertJsonPath('isHidden', true);
});

it('カスタム項目と値を削除できる', function () {
    [$project, $owner, $status] = projectOwnerForField();
    $field = ProjectField::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'task_status_id' => $status->id, 'seq_number' => 1]);
    TaskFieldValue::create(['task_id' => $task->id, 'project_field_id' => $field->id, 'value' => 'x']);

    $this->actingAs($owner)
        ->deleteJson("/api/projects/{$project->id}/fields/{$field->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('project_fields', ['id' => $field->id]);
    $this->assertDatabaseMissing('task_field_values', ['project_field_id' => $field->id]);
});

it('タスクにカスタム項目の値を設定して返す', function () {
    [$project, $owner, $status] = projectOwnerForField();
    $textField = ProjectField::factory()->type(ProjectFieldType::Text)->create(['project_id' => $project->id]);
    $selectField = ProjectField::factory()->type(ProjectFieldType::Select)->create(['project_id' => $project->id]);
    $opt = ProjectFieldOption::factory()->create(['project_field_id' => $selectField->id, 'label' => 'prod']);

    $task = $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'カスタム',
            'task_status_id' => $status->id,
            'custom_fields' => [
                $textField->id => 'hello',
                $selectField->id => $opt->id,
            ],
        ])
        ->assertCreated()
        ->json();

    $this->assertDatabaseHas('task_field_values', ['task_id' => $task['id'], 'project_field_id' => $textField->id]);

    $this->actingAs($owner)
        ->getJson("/api/tasks/{$task['id']}")
        ->assertOk()
        ->assertJsonCount(2, 'customFields');
});

it('不正なセレクト値を拒否する', function () {
    [$project, $owner, $status] = projectOwnerForField();
    $selectField = ProjectField::factory()->type(ProjectFieldType::Select)->create(['project_id' => $project->id]);
    ProjectFieldOption::factory()->create(['project_field_id' => $selectField->id]);

    $this->actingAs($owner)
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'X',
            'task_status_id' => $status->id,
            'custom_fields' => [$selectField->id => 999999],
        ])
        ->assertStatus(422);
});

it('カスタム項目を並べ替えられる', function () {
    [$project, $owner] = projectOwnerForField();
    $a = ProjectField::factory()->create(['project_id' => $project->id, 'position' => 0]);
    $b = ProjectField::factory()->create(['project_id' => $project->id, 'position' => 1]);

    $this->actingAs($owner)
        ->patchJson("/api/projects/{$project->id}/fields/reorder", ['ids' => [$b->id, $a->id]])
        ->assertOk();

    expect($b->fresh()->position)->toBe(0);
});

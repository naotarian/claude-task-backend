<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Enums\ProjectFieldType;
use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\OrganizationPosition;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\ProjectFieldOption;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskCategory;
use App\Models\TaskComment;
use App\Models\TaskFieldValue;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\DefaultProjectStatuses;
use App\Services\OrganizationProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ローカル/デモ用の初期データ。`make fresh` 後に alice@example.com / password
 * でログインして全機能を操作できる状態にする。
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, User> */
    private array $users = [];

    public function run(): void
    {
        $this->seedUsers();
        $this->seedAcme();
        $this->seedBeta();
    }

    private function seedUsers(): void
    {
        $defs = [
            'alice' => ['name' => '田中 アリス', 'email' => 'alice@example.com'],
            'bob' => ['name' => '鈴木 ボブ', 'email' => 'bob@example.com'],
            'carol' => ['name' => '佐藤 キャロル', 'email' => 'carol@example.com'],
            'dave' => ['name' => '高橋 デイブ', 'email' => 'dave@example.com'],
            'erin' => ['name' => '伊藤 エリン', 'email' => 'erin@example.com'],
            'freelancer' => ['name' => '山田 フリー', 'email' => 'freelancer@example.com'],
        ];

        $provisioner = app(OrganizationProvisioner::class);

        foreach ($defs as $key => $def) {
            $user = User::factory()->create([
                'name' => $def['name'],
                'email' => $def['email'],
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
            // 各ユーザーの personal 組織（フリーランス含む）
            $provisioner->provision("{$def['name']} (個人)", OrganizationType::Personal, $user);
            $this->users[$key] = $user;
        }
    }

    private function seedAcme(): void
    {
        $provisioner = app(OrganizationProvisioner::class);

        $acme = $provisioner->provision('Acme株式会社', OrganizationType::Organization, $this->users['alice']);
        $acme->update(['plan_id' => Plan::where('code', 'pro')->value('id')]);

        // メンバー（alice は provision で owner 済み）
        $this->addMember($acme, 'bob', OrganizationRole::Admin);
        $this->addMember($acme, 'carol', OrganizationRole::Member);
        $this->addMember($acme, 'dave', OrganizationRole::Member);

        // 役職（表示用ラベル）
        $ceo = OrganizationPosition::create(['organization_id' => $acme->id, 'name' => '社長', 'position' => 0]);
        $lead = OrganizationPosition::create(['organization_id' => $acme->id, 'name' => 'システム開発部部長', 'position' => 1]);
        $this->setPosition($acme, 'alice', $ceo->id);
        $this->setPosition($acme, 'bob', $lead->id);

        // pending 招待
        OrganizationInvitation::create([
            'organization_id' => $acme->id,
            'email' => 'newcomer@example.com',
            'role' => OrganizationRole::Member,
            'token' => Str::random(64),
            'invited_by_user_id' => $this->users['alice']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->seedEcProject($acme);
        $this->seedToolProject($acme);
    }

    private function seedBeta(): void
    {
        $provisioner = app(OrganizationProvisioner::class);
        $beta = $provisioner->provision('Beta合同会社', OrganizationType::Organization, $this->users['erin']);
        $beta->update(['plan_id' => Plan::where('code', 'free')->value('id')]);

        $project = $this->createProject($beta, 'POC', '新規事業PoC', 'フリープランのデモ用プロジェクト', true);
        $this->addProjectMember($project, 'erin', ProjectRole::Owner);
        $statuses = $this->statusMap($project);

        $seq = 0;
        $this->createTask($project, $seq, [
            'title' => '市場調査', 'status' => '処理中', 'priority' => TaskPriority::High,
            'assignees' => ['erin'], 'reporter' => 'erin', 'estimated' => 8,
        ], $statuses, [], []);
        $this->createTask($project, $seq, [
            'title' => '競合分析レポート作成', 'status' => '未対応', 'priority' => TaskPriority::Normal,
            'assignees' => ['erin'], 'reporter' => 'erin',
        ], $statuses, [], []);
        $project->update(['task_sequence' => $seq]);
    }

    private function seedEcProject(Organization $acme): void
    {
        $project = $this->createProject($acme, 'EC', 'ECサイト構築', 'デモ用のメインプロジェクト。各機能を一通り含む。', true);

        foreach (['alice' => ProjectRole::Owner, 'bob' => ProjectRole::Editor, 'carol' => ProjectRole::Editor, 'dave' => ProjectRole::Viewer, 'freelancer' => ProjectRole::Editor] as $key => $role) {
            $this->addProjectMember($project, $key, $role);
        }

        // カテゴリ
        $categories = [];
        foreach (['設計', 'デザイン', '実装', 'テスト', 'バグ修正', '調査'] as $i => $name) {
            $categories[$name] = TaskCategory::create(['project_id' => $project->id, 'name' => $name, 'position' => $i]);
        }

        // カスタム項目
        $deployField = ProjectField::create([
            'project_id' => $project->id, 'name' => '本番デプロイ日時', 'type' => ProjectFieldType::Datetime, 'position' => 0,
        ]);
        $envField = ProjectField::create([
            'project_id' => $project->id, 'name' => '環境', 'type' => ProjectFieldType::Select, 'position' => 1,
        ]);
        $envOptions = [];
        foreach (['dev', 'staging', 'prod'] as $i => $label) {
            $envOptions[$label] = ProjectFieldOption::create(['project_field_id' => $envField->id, 'label' => $label, 'position' => $i]);
        }

        $statuses = $this->statusMap($project);
        $fields = ['deploy' => $deployField, 'env' => $envField, 'envOptions' => $envOptions];

        $specs = [
            ['title' => '要件定義', 'status' => '完了', 'category' => '設計', 'priority' => TaskPriority::High, 'assignees' => ['alice'], 'reporter' => 'alice', 'progress' => 100, 'pstart' => '-30 days', 'pend' => '-24 days', 'astart' => '-30 days', 'aend' => '-23 days', 'estimated' => 16, 'worklogs' => [['alice', 6, '-29 days'], ['alice', 6, '-26 days'], ['bob', 5, '-24 days']], 'comments' => [['bob', '要件レビュー完了しました。']], 'description' => "# 要件定義\n\n- ユーザーストーリー整理\n- 画面一覧\n"],
            ['title' => 'DB設計', 'status' => '完了', 'category' => '設計', 'priority' => TaskPriority::Normal, 'assignees' => ['bob'], 'reporter' => 'alice', 'progress' => 100, 'pstart' => '-23 days', 'pend' => '-20 days', 'astart' => '-23 days', 'aend' => '-19 days', 'estimated' => 12, 'worklogs' => [['bob', 7, '-22 days'], ['bob', 6, '-20 days']]],
            ['title' => 'トップページデザイン', 'status' => '処理済み', 'category' => 'デザイン', 'priority' => TaskPriority::Normal, 'assignees' => ['carol'], 'reporter' => 'alice', 'progress' => 90, 'pstart' => '-18 days', 'pend' => '-14 days', 'astart' => '-18 days', 'estimated' => 20, 'worklogs' => [['carol', 8, '-17 days'], ['carol', 8, '-15 days']], 'comments' => [['alice', 'いい感じです！'], ['carol', '修正版あげました。']]],
            ['title' => '商品一覧API実装', 'status' => '処理中', 'category' => '実装', 'priority' => TaskPriority::High, 'assignees' => ['bob', 'freelancer'], 'reporter' => 'alice', 'progress' => 60, 'due' => '+3 days', 'pstart' => '-7 days', 'pend' => '+3 days', 'astart' => '-7 days', 'estimated' => 24, 'worklogs' => [['bob', 6, '-6 days'], ['freelancer', 8, '-4 days'], ['freelancer', 4, '-1 days']], 'comments' => [['freelancer', 'ページネーション対応中です。']], 'fields' => ['env' => 'staging'], 'description' => "## 商品一覧API\n\n- [ ] 一覧取得\n- [x] フィルタ\n- [ ] ページネーション"],
            ['title' => 'カート機能実装', 'status' => '処理中', 'category' => '実装', 'priority' => TaskPriority::High, 'assignees' => ['freelancer'], 'reporter' => 'bob', 'progress' => 40, 'due' => '+7 days', 'pstart' => '-3 days', 'pend' => '+7 days', 'astart' => '-2 days', 'estimated' => 30, 'worklogs' => [['freelancer', 6, '-2 days']]],
            ['title' => '決済連携（Stripe）', 'status' => '未対応', 'category' => '実装', 'priority' => TaskPriority::High, 'assignees' => ['bob'], 'reporter' => 'alice', 'due' => '+14 days', 'pstart' => '+7 days', 'pend' => '+14 days', 'estimated' => 40, 'fields' => ['env' => 'prod', 'deploy' => '+20 days 10:00']],
            ['title' => 'ログイン画面デザイン', 'status' => '未対応', 'category' => 'デザイン', 'priority' => TaskPriority::Low, 'assignees' => ['carol'], 'reporter' => 'alice', 'due' => '+10 days', 'estimated' => 8],
            ['title' => '本番デプロイ手順整備', 'status' => '未対応', 'category' => '調査', 'priority' => TaskPriority::Normal, 'assignees' => ['bob'], 'reporter' => 'bob', 'estimated' => 6, 'fields' => ['env' => 'prod']],
            ['title' => '検索が遅い不具合', 'status' => '処理中', 'category' => 'バグ修正', 'priority' => TaskPriority::High, 'assignees' => ['freelancer', 'bob'], 'reporter' => 'carol', 'progress' => 30, 'due' => '+2 days', 'astart' => '-1 days', 'estimated' => 5, 'worklogs' => [['freelancer', 3, '-1 days']], 'comments' => [['carol', 'インデックス不足かもしれません。']]],
            ['title' => 'スマホ表示崩れ', 'status' => '未対応', 'category' => 'バグ修正', 'priority' => TaskPriority::Normal, 'assignees' => ['carol'], 'reporter' => 'dave', 'due' => '+5 days', 'estimated' => 3],
            ['title' => 'E2Eテスト作成', 'status' => '未割当', 'category' => 'テスト', 'priority' => TaskPriority::Normal, 'assignees' => [], 'reporter' => 'alice', 'estimated' => 16],
            ['title' => '負荷試験', 'status' => '未割当', 'category' => 'テスト', 'priority' => TaskPriority::Low, 'assignees' => [], 'reporter' => 'alice'],
            ['title' => '管理画面のCSV出力', 'status' => '未対応', 'category' => '実装', 'priority' => TaskPriority::Low, 'assignees' => ['freelancer'], 'reporter' => 'bob', 'estimated' => 10],
        ];

        $seq = 0;
        $created = [];
        foreach ($specs as $spec) {
            $created[$spec['title']] = $this->createTask($project, $seq, $spec, $statuses, $categories, $fields);
        }

        // サブタスク（親=商品一覧API実装）
        $this->createTask($project, $seq, [
            'title' => '商品検索のクエリ最適化', 'status' => '未対応', 'category' => '実装', 'priority' => TaskPriority::Normal,
            'assignees' => ['freelancer'], 'reporter' => 'bob', 'estimated' => 4, 'parent' => $created['商品一覧API実装'],
        ], $statuses, $categories, $fields);

        $project->update(['task_sequence' => $seq]);

        // 添付（MinIO。失敗してもデモ全体は継続）
        $this->attachDemoFile($created['要件定義'], 'requirements.md', "# 要件定義書\n\nデモ用の添付ファイルです。\n");
    }

    private function seedToolProject(Organization $acme): void
    {
        // カテゴリ機能 OFF のプロジェクト
        $project = $this->createProject($acme, 'TOOL', '社内ツール改善', 'カテゴリ機能をOFFにしたデモ用プロジェクト。', false);
        $this->addProjectMember($project, 'alice', ProjectRole::Owner);
        $this->addProjectMember($project, 'bob', ProjectRole::Editor);

        $statuses = $this->statusMap($project);
        $seq = 0;
        $this->createTask($project, $seq, [
            'title' => '勤怠CSVの自動取込', 'status' => '処理中', 'priority' => TaskPriority::Normal,
            'assignees' => ['bob'], 'reporter' => 'alice', 'progress' => 50, 'estimated' => 8,
            'worklogs' => [['bob', 4, '-1 days']],
        ], $statuses, [], []);
        $this->createTask($project, $seq, [
            'title' => 'Slack通知の整理', 'status' => '未対応', 'priority' => TaskPriority::Low,
            'assignees' => ['alice'], 'reporter' => 'alice',
        ], $statuses, [], []);
        $project->update(['task_sequence' => $seq]);
    }

    // ---- helpers ----

    private function addMember(Organization $org, string $userKey, OrganizationRole $role): void
    {
        OrganizationMember::create([
            'organization_id' => $org->id,
            'user_id' => $this->users[$userKey]->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    private function setPosition(Organization $org, string $userKey, int $positionId): void
    {
        OrganizationMember::where('organization_id', $org->id)
            ->where('user_id', $this->users[$userKey]->id)
            ->update(['position_id' => $positionId]);
    }

    private function createProject(Organization $org, string $key, string $name, string $description, bool $categoriesEnabled): Project
    {
        $project = Project::create([
            'organization_id' => $org->id,
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'status' => \App\Enums\ProjectStatus::Active,
            'categories_enabled' => $categoriesEnabled,
            'task_sequence' => 0,
        ]);
        app(DefaultProjectStatuses::class)->seed($project);

        return $project;
    }

    private function addProjectMember(Project $project, string $userKey, ProjectRole $role): void
    {
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->users[$userKey]->id,
            'role' => $role,
        ]);
    }

    /** @return array<string, TaskStatus> 名前→ステータス */
    private function statusMap(Project $project): array
    {
        return $project->statuses()->get()->keyBy('name')->all();
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array<string, TaskStatus>  $statuses
     * @param  array<string, TaskCategory>  $categories
     * @param  array<string, mixed>  $fields
     */
    private function createTask(Project $project, int &$seq, array $spec, array $statuses, array $categories, array $fields): Task
    {
        $seq++;
        $task = Task::create([
            'project_id' => $project->id,
            'task_status_id' => $statuses[$spec['status']]->id,
            'task_category_id' => isset($spec['category']) ? ($categories[$spec['category']]->id ?? null) : null,
            'seq_number' => $seq,
            'title' => $spec['title'],
            'description' => $spec['description'] ?? null,
            'created_by_user_id' => $this->users[$spec['reporter']]->id,
            'parent_task_id' => isset($spec['parent']) ? $spec['parent']->id : null,
            'priority' => $spec['priority'] ?? TaskPriority::Normal,
            'progress' => $spec['progress'] ?? 0,
            'due_date' => isset($spec['due']) ? Carbon::parse($spec['due'])->toDateString() : null,
            'planned_start_date' => isset($spec['pstart']) ? Carbon::parse($spec['pstart'])->toDateString() : null,
            'planned_end_date' => isset($spec['pend']) ? Carbon::parse($spec['pend'])->toDateString() : null,
            'actual_start_date' => isset($spec['astart']) ? Carbon::parse($spec['astart'])->toDateString() : null,
            'actual_end_date' => isset($spec['aend']) ? Carbon::parse($spec['aend'])->toDateString() : null,
            'estimated_hours' => $spec['estimated'] ?? null,
            'actual_hours' => 0,
        ]);

        // 担当者（複数）
        $assigneeIds = array_map(fn ($k) => $this->users[$k]->id, $spec['assignees'] ?? []);
        $task->assignees()->sync($assigneeIds);

        // 工数記録 → actual_hours 集計
        $total = 0;
        foreach ($spec['worklogs'] ?? [] as [$userKey, $hours, $date]) {
            WorkLog::create([
                'task_id' => $task->id,
                'user_id' => $this->users[$userKey]->id,
                'worked_on' => Carbon::parse($date)->toDateString(),
                'hours' => $hours,
            ]);
            $total += $hours;
        }
        if ($total > 0) {
            $task->update(['actual_hours' => $total]);
        }

        // コメント
        foreach ($spec['comments'] ?? [] as [$userKey, $body]) {
            TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $this->users[$userKey]->id,
                'body' => $body,
            ]);
        }

        // カスタム項目値
        $specFields = $spec['fields'] ?? [];
        if (isset($specFields['env']) && isset($fields['envOptions'])) {
            TaskFieldValue::create([
                'task_id' => $task->id,
                'project_field_id' => $fields['env']->id,
                'value' => $fields['envOptions'][$specFields['env']]->id,
            ]);
        }
        if (isset($specFields['deploy']) && isset($fields['deploy'])) {
            TaskFieldValue::create([
                'task_id' => $task->id,
                'project_field_id' => $fields['deploy']->id,
                'value' => Carbon::parse($specFields['deploy'])->toIso8601String(),
            ]);
        }

        return $task;
    }

    private function attachDemoFile(Task $task, string $name, string $contents): void
    {
        try {
            $path = "attachments/{$task->project_id}/".Str::uuid()->toString()."-{$name}";
            Storage::disk('s3')->put($path, $contents);
            TaskAttachment::create([
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'uploaded_by_user_id' => $task->created_by_user_id,
                'disk' => 's3',
                'path' => $path,
                'original_name' => $name,
                'size_bytes' => strlen($contents),
                'mime_type' => 'text/markdown',
            ]);
        } catch (\Throwable $e) {
            $this->command?->warn("添付のシードをスキップ（ストレージ未接続）: {$e->getMessage()}");
        }
    }
}

<?php

namespace App\Services;

use App\Enums\TaskStatusCategory;
use App\Models\Project;
use App\Models\TaskStatus;

/**
 * Seeds the default set of task statuses for a newly created project
 * (mirrors Backlog: 未対応 / 処理中 / 処理済み / 完了).
 *
 * Runs inside the caller's transaction.
 */
class DefaultProjectStatuses
{
    /**
     * @var array<int, array{name: string, color: string, category: TaskStatusCategory, protected?: bool}>
     */
    private const DEFAULTS = [
        ['name' => '未割当', 'color' => '#d1d5db', 'category' => TaskStatusCategory::Todo, 'protected' => true],
        ['name' => '未対応', 'color' => '#9ca3af', 'category' => TaskStatusCategory::Todo],
        ['name' => '処理中', 'color' => '#3b82f6', 'category' => TaskStatusCategory::InProgress],
        ['name' => '処理済み', 'color' => '#8b5cf6', 'category' => TaskStatusCategory::InProgress],
        ['name' => '完了', 'color' => '#22c55e', 'category' => TaskStatusCategory::Done],
    ];

    public function seed(Project $project): void
    {
        foreach (self::DEFAULTS as $position => $status) {
            TaskStatus::query()->create([
                'project_id' => $project->id,
                'name' => $status['name'],
                'color' => $status['color'],
                'category' => $status['category'],
                'position' => $position,
                'is_protected' => $status['protected'] ?? false,
            ]);
        }
    }
}

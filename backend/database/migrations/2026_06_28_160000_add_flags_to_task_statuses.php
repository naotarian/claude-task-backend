<?php

use App\Models\Project;
use App\Models\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_statuses', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('category');
            // Protected statuses (the "未割当" bucket) cannot be deleted.
            $table->boolean('is_protected')->default(false)->after('is_hidden');
        });

        // Backfill an "未割当" protected status (position 0) for existing projects.
        Project::query()->each(function (Project $project) {
            $exists = TaskStatus::query()
                ->where('project_id', $project->id)
                ->where('is_protected', true)
                ->exists();

            if (! $exists) {
                TaskStatus::query()
                    ->where('project_id', $project->id)
                    ->increment('position');

                TaskStatus::query()->create([
                    'project_id' => $project->id,
                    'name' => '未割当',
                    'color' => '#d1d5db',
                    'category' => 'todo',
                    'position' => 0,
                    'is_protected' => true,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_statuses', function (Blueprint $table) {
            $table->dropColumn(['is_hidden', 'is_protected']);
        });
    }
};

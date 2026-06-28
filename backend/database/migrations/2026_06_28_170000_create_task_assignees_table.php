<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tasks can have multiple assignees.
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        // Migrate the existing single assignee into the pivot.
        DB::table('tasks')->whereNotNull('assignee_user_id')->orderBy('id')->each(function ($task) {
            DB::table('task_assignees')->insert([
                'task_id' => $task->id,
                'user_id' => $task->assignee_user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignee_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assignee_user_id')->nullable()->after('description')
                ->constrained('users')->nullOnDelete();
        });

        Schema::dropIfExists('task_assignees');
    }
};

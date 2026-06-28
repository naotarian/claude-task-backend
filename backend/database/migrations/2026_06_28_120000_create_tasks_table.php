<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_status_id')->constrained('task_statuses');
            $table->unsignedInteger('seq_number'); // display as KEY-{seq_number}
            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();

            $table->string('priority')->default('normal');
            $table->unsignedTinyInteger('progress')->default(0); // 0-100

            // Schedule (plan vs actual)
            $table->date('due_date')->nullable();          // 納期
            $table->date('planned_start_date')->nullable(); // 予定開始
            $table->date('planned_end_date')->nullable();   // 予定終了
            $table->date('actual_start_date')->nullable();  // 実績開始
            $table->date('actual_end_date')->nullable();    // 実績終了

            // Effort (plan vs actual, in hours)
            $table->decimal('estimated_hours', 8, 2)->nullable(); // 予定工数
            $table->decimal('actual_hours', 8, 2)->default(0);    // 実績工数 (sum of work logs)

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'seq_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

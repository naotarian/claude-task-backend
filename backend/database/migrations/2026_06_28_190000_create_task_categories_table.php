<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Project-defined task categories (設計 / 実装 / テスト ...). No system
        // defaults; a task with no category is treated as "未割り当て".
        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('task_category_id')->nullable()->after('task_status_id')
                ->constrained('task_categories')->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('categories_enabled')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_category_id');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('categories_enabled');
        });
        Schema::dropIfExists('task_categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // Owning / billing organization (quota is attributed here).
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // e.g. "PROJ" (unique per organization)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            // Per-project running counter used to build task numbers (PROJ-123).
            $table->unsignedInteger('task_sequence')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

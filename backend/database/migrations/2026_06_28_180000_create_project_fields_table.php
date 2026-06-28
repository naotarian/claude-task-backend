<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // datetime | text | number | select | multiselect
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('project_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_field_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('task_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_field_id')->constrained()->cascadeOnDelete();
            // Stored per type: datetime=ISO string / text=string / number=number
            // / select=option id / multiselect=array of option ids.
            $table->json('value');
            $table->timestamps();

            $table->unique(['task_id', 'project_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_field_values');
        Schema::dropIfExists('project_field_options');
        Schema::dropIfExists('project_fields');
    }
};

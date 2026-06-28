<?php

namespace App\Models;

use App\Enums\TaskStatusCategory;
use Database\Factories\TaskStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    /** @use HasFactory<TaskStatusFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'category',
        'position',
        'is_hidden',
        'is_protected',
    ];

    protected function casts(): array
    {
        return [
            'category' => TaskStatusCategory::class,
            'is_hidden' => 'boolean',
            'is_protected' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

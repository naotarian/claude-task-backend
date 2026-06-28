<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskFieldValue extends Model
{
    protected $fillable = [
        'task_id',
        'project_field_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<ProjectField, $this> */
    public function field(): BelongsTo
    {
        return $this->belongsTo(ProjectField::class, 'project_field_id');
    }
}

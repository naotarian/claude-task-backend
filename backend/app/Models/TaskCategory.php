<?php

namespace App\Models;

use Database\Factories\TaskCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCategory extends Model
{
    /** @use HasFactory<TaskCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'position',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

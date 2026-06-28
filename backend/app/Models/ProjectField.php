<?php

namespace App\Models;

use App\Enums\ProjectFieldType;
use Database\Factories\ProjectFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectField extends Model
{
    /** @use HasFactory<ProjectFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'position',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectFieldType::class,
            'is_hidden' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<ProjectFieldOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ProjectFieldOption::class)->orderBy('position');
    }
}

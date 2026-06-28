<?php

namespace App\Models;

use Database\Factories\ProjectFieldOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFieldOption extends Model
{
    /** @use HasFactory<ProjectFieldOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'project_field_id',
        'label',
        'position',
    ];

    /** @return BelongsTo<ProjectField, $this> */
    public function field(): BelongsTo
    {
        return $this->belongsTo(ProjectField::class, 'project_field_id');
    }
}

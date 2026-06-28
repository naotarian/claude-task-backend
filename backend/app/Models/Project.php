<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'key',
        'name',
        'description',
        'status',
        'categories_enabled',
        'task_sequence',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'categories_enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<ProjectMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<TaskCategory, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(TaskCategory::class)->orderBy('position');
    }

    /** @return HasMany<TaskStatus, $this> */
    public function statuses(): HasMany
    {
        return $this->hasMany(TaskStatus::class)->orderBy('position');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<ProjectField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(ProjectField::class)->orderBy('position');
    }

    public function memberRole(User $user): ?ProjectRole
    {
        $member = $this->members()->where('user_id', $user->id)->first();

        return $member?->role;
    }
}

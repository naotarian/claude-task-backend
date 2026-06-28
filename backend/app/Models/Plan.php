<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    public const FREE = 'free';

    protected $fillable = [
        'code',
        'name',
        'price_monthly',
        'max_projects',
        'max_members_per_project',
        'max_storage_bytes_per_project',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'integer',
            'max_projects' => 'integer',
            'max_members_per_project' => 'integer',
            'max_storage_bytes_per_project' => 'integer',
        ];
    }

    public function isFree(): bool
    {
        return $this->code === self::FREE;
    }
}

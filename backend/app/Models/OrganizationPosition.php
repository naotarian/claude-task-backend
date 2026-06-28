<?php

namespace App\Models;

use Database\Factories\OrganizationPositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationPosition extends Model
{
    /** @use HasFactory<OrganizationPositionFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'position',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

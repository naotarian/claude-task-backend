<?php

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum OrganizationRole: string
{
    /** Billing owner; can delete the organization. */
    case Owner = 'owner';

    /** Can manage members and projects. */
    case Admin = 'admin';

    /** Regular member. */
    case Member = 'member';

    /**
     * Roles that may administer the organization (members, projects, billing view).
     *
     * @return array<int, self>
     */
    public static function managers(): array
    {
        return [self::Owner, self::Admin];
    }

    public function canManage(): bool
    {
        return in_array($this, self::managers(), true);
    }
}

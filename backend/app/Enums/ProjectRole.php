<?php

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ProjectRole: string
{
    /** Full control: project settings, members, archive, and all task editing. */
    case Owner = 'owner';

    /** Can create/edit tasks, but not project-wide settings. */
    case Editor = 'editor';

    /** Read-only. */
    case Viewer = 'viewer';

    public function canManage(): bool
    {
        return $this === self::Owner;
    }

    public function canEdit(): bool
    {
        return $this === self::Owner || $this === self::Editor;
    }
}

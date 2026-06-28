<?php

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ProjectStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}

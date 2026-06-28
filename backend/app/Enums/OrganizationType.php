<?php

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum OrganizationType: string
{
    /** A company / team that can have many members. */
    case Organization = 'organization';

    /** A freelancer's single-person organization (auto-created on signup). */
    case Personal = 'personal';
}

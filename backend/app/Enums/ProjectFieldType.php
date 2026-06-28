<?php

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ProjectFieldType: string
{
    case Datetime = 'datetime';
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';        // 単一選択
    case Multiselect = 'multiselect'; // 複数選択

    public function hasOptions(): bool
    {
        return $this === self::Select || $this === self::Multiselect;
    }
}

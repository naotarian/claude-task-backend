<?php

namespace App\Data;

use App\Models\WorkLog;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WorkLogData extends Data
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public string $workedOn,
        public float $hours,
        public ?string $note,
    ) {}

    public static function fromModel(WorkLog $log): self
    {
        return new self(
            id: $log->id,
            userId: $log->user_id,
            userName: $log->relationLoaded('user') ? $log->user->name : '',
            workedOn: $log->worked_on->toDateString(),
            hours: (float) $log->hours,
            note: $log->note,
        );
    }
}

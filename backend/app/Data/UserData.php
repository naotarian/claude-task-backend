<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Output DTO for a user. This is also the source of truth for the
 * TypeScript `App.Data.UserData` type consumed by the Next.js frontend.
 */
#[TypeScript]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $avatarPath,
        public bool $emailVerified,
        public string $createdAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            avatarPath: $user->avatar_path,
            emailVerified: $user->hasVerifiedEmail(),
            createdAt: $user->created_at->toIso8601String(),
        );
    }
}

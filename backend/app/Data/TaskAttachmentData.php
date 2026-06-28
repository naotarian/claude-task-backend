<?php

namespace App\Data;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskAttachmentData extends Data
{
    public function __construct(
        public int $id,
        public int $taskId,
        public string $originalName,
        public int $sizeBytes,
        public ?string $mimeType,
        public string $url,
        public ?string $uploadedByName,
        public string $createdAt,
    ) {}

    public static function fromModel(TaskAttachment $attachment): self
    {
        return new self(
            id: $attachment->id,
            taskId: $attachment->task_id,
            originalName: $attachment->original_name,
            sizeBytes: $attachment->size_bytes,
            mimeType: $attachment->mime_type,
            url: Storage::disk($attachment->disk)->url($attachment->path),
            uploadedByName: $attachment->relationLoaded('uploadedBy') ? $attachment->uploadedBy?->name : null,
            createdAt: $attachment->created_at->toIso8601String(),
        );
    }
}

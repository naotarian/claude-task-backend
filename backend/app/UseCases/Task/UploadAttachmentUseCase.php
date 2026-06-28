<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Services\QuotaService;
use App\UseCases\AbstractUseCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UploadAttachmentUseCase extends AbstractUseCase
{
    private const DISK = 's3';

    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly ProjectRepositoryInterface $projects,
        private readonly QuotaService $quota,
    ) {}

    public function handle(Task $task, User $uploader, UploadedFile $file): TaskAttachment
    {
        $project = $task->project;

        // Enforce the per-project storage quota (owning organization's plan).
        $currentBytes = $this->projects->storageBytesForProject($project);
        $this->quota->assertCanUpload($project, (int) $file->getSize(), $currentBytes);

        // Store the binary first (outside the DB transaction).
        $path = $file->store("attachments/{$project->id}", self::DISK);

        try {
            return $this->transaction(function () use ($task, $project, $uploader, $file, $path): TaskAttachment {
                $attachment = $this->tasks->addAttachment($task, [
                    'project_id' => $project->id,
                    'uploaded_by_user_id' => $uploader->id,
                    'disk' => self::DISK,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size_bytes' => (int) $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                ]);

                return $attachment->load('uploadedBy');
            });
        } catch (Throwable $e) {
            // Roll back the orphaned file if the DB write failed.
            Storage::disk(self::DISK)->delete($path);
            throw $e;
        }
    }
}

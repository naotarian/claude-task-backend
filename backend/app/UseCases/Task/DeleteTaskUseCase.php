<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\UseCases\AbstractUseCase;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a task and its attachments. Attachment files are removed from
 * storage (freeing the project's storage quota) and their records are
 * hard-deleted before the task itself is soft-deleted.
 */
class DeleteTaskUseCase extends AbstractUseCase
{
    public function handle(Task $task): void
    {
        $attachments = $task->attachments()->get();

        $this->transaction(function () use ($task, $attachments): void {
            foreach ($attachments as $attachment) {
                $attachment->delete();
            }
            $task->delete();
        });

        // Remove the binaries after the DB changes commit.
        foreach ($attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }
    }
}

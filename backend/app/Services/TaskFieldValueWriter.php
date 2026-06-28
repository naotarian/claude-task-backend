<?php

namespace App\Services;

use App\Enums\ProjectFieldType;
use App\Models\Project;
use App\Models\ProjectField;
use App\Models\Task;
use App\Models\TaskFieldValue;
use Illuminate\Validation\ValidationException;

/**
 * Validates and persists custom field values for a task, by field type.
 * Runs inside the calling use case's transaction.
 */
class TaskFieldValueWriter
{
    /**
     * @param  array<int|string, mixed>  $values  field_id => value
     */
    public function write(Task $task, Project $project, array $values): void
    {
        $fields = $project->fields()->where('is_hidden', false)->with('options')->get()->keyBy('id');

        foreach ($values as $fieldId => $raw) {
            $field = $fields->get((int) $fieldId);

            if ($field === null) {
                throw ValidationException::withMessages([
                    "custom_fields.{$fieldId}" => ['不明な項目です。'],
                ]);
            }

            if ($raw === null || $raw === '' || $raw === []) {
                TaskFieldValue::query()
                    ->where('task_id', $task->id)
                    ->where('project_field_id', $field->id)
                    ->delete();

                continue;
            }

            $normalized = $this->normalize($field, $raw, (int) $fieldId);

            TaskFieldValue::query()->updateOrCreate(
                ['task_id' => $task->id, 'project_field_id' => $field->id],
                ['value' => $normalized],
            );
        }
    }

    private function normalize(ProjectField $field, mixed $raw, int $fieldId): mixed
    {
        $optionIds = $field->options->pluck('id')->all();
        $fail = fn (string $msg) => throw ValidationException::withMessages(["custom_fields.{$fieldId}" => [$msg]]);

        return match ($field->type) {
            ProjectFieldType::Text => (string) $raw,
            ProjectFieldType::Number => is_numeric($raw) ? (float) $raw : $fail('数値を入力してください。'),
            ProjectFieldType::Datetime => strtotime((string) $raw) !== false ? (string) $raw : $fail('日時の形式が不正です。'),
            ProjectFieldType::Select => in_array((int) $raw, $optionIds, true) ? (int) $raw : $fail('選択肢が不正です。'),
            ProjectFieldType::Multiselect => $this->normalizeMulti($raw, $optionIds, $fail),
        };
    }

    /**
     * @param  array<int, int>  $optionIds
     * @return array<int, int>
     */
    private function normalizeMulti(mixed $raw, array $optionIds, callable $fail): array
    {
        if (! is_array($raw)) {
            $fail('複数選択の値が不正です。');
        }
        $ids = array_map('intval', $raw);
        foreach ($ids as $id) {
            if (! in_array($id, $optionIds, true)) {
                $fail('選択肢が不正です。');
            }
        }

        return array_values($ids);
    }
}

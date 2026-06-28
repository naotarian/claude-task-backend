<?php

namespace App\UseCases\Project;

use App\Models\ProjectField;
use App\Repositories\Contracts\ProjectFieldRepositoryInterface;
use App\UseCases\AbstractUseCase;

class UpdateProjectFieldUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly ProjectFieldRepositoryInterface $fields,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>|null  $options
     */
    public function handle(ProjectField $field, array $attributes, ?array $options): ProjectField
    {
        return $this->transaction(function () use ($field, $attributes, $options): ProjectField {
            if (! empty($attributes)) {
                $this->fields->update($field, $attributes);
            }

            if ($options !== null && $field->type->hasOptions()) {
                $this->fields->replaceOptions($field, $options);
            }

            return $field->load('options');
        });
    }
}

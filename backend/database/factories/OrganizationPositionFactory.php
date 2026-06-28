<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationPosition>
 */
class OrganizationPositionFactory extends Factory
{
    protected $model = OrganizationPosition::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->jobTitle(),
            'position' => 0,
        ];
    }
}

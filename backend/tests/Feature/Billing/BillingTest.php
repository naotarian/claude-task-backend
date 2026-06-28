<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlanSeeder;

it('利用可能なプラン一覧を取得できる', function () {
    $this->seed(PlanSeeder::class);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/plans')
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonPath('0.code', 'free');
});

it('有効なプランと利用状況の課金サマリを返す', function () {
    $this->seed(PlanSeeder::class);
    $user = User::factory()->create();
    $org = Organization::factory()->create(['owner_user_id' => $user->id, 'plan_id' => null]);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => OrganizationRole::Owner,
    ]);
    Project::factory()->count(2)->create(['organization_id' => $org->id]);

    $this->actingAs($user)
        ->getJson("/api/organizations/{$org->id}/billing")
        ->assertOk()
        ->assertJsonPath('plan.code', 'free')
        ->assertJsonPath('plan.maxProjects', 3)
        ->assertJsonPath('projectCount', 2);
});

it('課金サマリに有料プランが反映される', function () {
    $plan = Plan::factory()->paid()->create();
    $user = User::factory()->create();
    $org = Organization::factory()->create(['owner_user_id' => $user->id, 'plan_id' => $plan->id]);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => OrganizationRole::Owner,
    ]);

    $this->actingAs($user)
        ->getJson("/api/organizations/{$org->id}/billing")
        ->assertOk()
        ->assertJsonPath('plan.code', 'pro')
        ->assertJsonPath('plan.maxProjects', null);
});

<?php

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;

it('登録時に個人組織が自動作成される', function () {
    $this->postJson('/api/register', [
        'name' => '一郎',
        'email' => 'ichiro@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', 'ichiro@example.com')->first();

    $org = Organization::where('owner_user_id', $user->id)->first();
    expect($org)->not->toBeNull();
    expect($org->type)->toBe(OrganizationType::Personal);

    $this->assertDatabaseHas('organization_members', [
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => OrganizationRole::Owner->value,
    ]);
});

it('作成者をオーナーとして組織を作成できる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/organizations', ['name' => 'Acme Inc'])
        ->assertCreated()
        ->assertJsonPath('name', 'Acme Inc')
        ->assertJsonPath('type', 'organization')
        ->assertJsonPath('role', 'owner');

    $this->assertDatabaseHas('organizations', ['name' => 'Acme Inc', 'type' => 'organization']);
});

it('所属する組織のみ一覧取得できる', function () {
    $user = User::factory()->create();
    $mine = Organization::factory()->create();
    OrganizationMember::factory()->create([
        'organization_id' => $mine->id,
        'user_id' => $user->id,
        'role' => OrganizationRole::Member,
    ]);
    Organization::factory()->create(); // someone else's

    $this->actingAs($user)
        ->getJson('/api/organizations')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $mine->id);
});

it('所属していない組織は閲覧できない', function () {
    $user = User::factory()->create();
    $other = Organization::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/organizations/{$other->id}")
        ->assertForbidden();
});

it('メンバーは組織を閲覧できる', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'role' => OrganizationRole::Member,
    ]);

    $this->actingAs($user)
        ->getJson("/api/organizations/{$org->id}")
        ->assertOk()
        ->assertJsonPath('id', $org->id)
        ->assertJsonPath('role', 'member');
});

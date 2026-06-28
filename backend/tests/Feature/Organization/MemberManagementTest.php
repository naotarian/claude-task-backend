<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationPosition;
use App\Models\User;

/**
 * @return array{0: Organization, 1: User, 2: OrganizationMember}
 */
function orgOwnerAndMember(): array
{
    $owner = User::factory()->create();
    $org = Organization::factory()->create(['owner_user_id' => $owner->id]);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => OrganizationRole::Owner,
    ]);
    $memberUser = User::factory()->create();
    $member = OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $memberUser->id,
        'role' => OrganizationRole::Member,
    ]);

    return [$org, $owner, $member];
}

it('オーナーはメンバーをオーナーに昇格できる（複数オーナー可）', function () {
    [$org, $owner, $member] = orgOwnerAndMember();

    $this->actingAs($owner)
        ->patchJson("/api/organizations/{$org->id}/members/{$member->id}", ['role' => 'owner'])
        ->assertOk()
        ->assertJsonPath('role', 'owner');

    $this->assertDatabaseHas('organization_members', [
        'id' => $member->id,
        'role' => 'owner',
    ]);
});

it('組織の最後のオーナーの降格を防ぐ', function () {
    [$org, $owner] = orgOwnerAndMember();
    $ownerMember = OrganizationMember::where('organization_id', $org->id)
        ->where('user_id', $owner->id)->first();

    $this->actingAs($owner)
        ->patchJson("/api/organizations/{$org->id}/members/{$ownerMember->id}", ['role' => 'member'])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('role');
});

it('オーナー以外は権限を変更できない', function () {
    [$org, , $member] = orgOwnerAndMember();
    $admin = User::factory()->create();
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $admin->id,
        'role' => OrganizationRole::Admin,
    ]);

    $this->actingAs($admin)
        ->patchJson("/api/organizations/{$org->id}/members/{$member->id}", ['role' => 'owner'])
        ->assertForbidden();
});

it('役職を作成してメンバーに割り当てられる', function () {
    [$org, $owner, $member] = orgOwnerAndMember();

    $position = $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/positions", ['name' => 'システム開発部部長'])
        ->assertCreated()
        ->assertJsonPath('name', 'システム開発部部長')
        ->json();

    $this->actingAs($owner)
        ->patchJson("/api/organizations/{$org->id}/members/{$member->id}", ['position_id' => $position['id']])
        ->assertOk()
        ->assertJsonPath('positionName', 'システム開発部部長');
});

it('メンバーを役職ラベル付きで一覧取得できる', function () {
    [$org, $owner, $member] = orgOwnerAndMember();
    $position = OrganizationPosition::factory()->create([
        'organization_id' => $org->id,
        'name' => '社長',
    ]);
    $member->update(['position_id' => $position->id]);

    $this->actingAs($owner)
        ->getJson("/api/organizations/{$org->id}/members")
        ->assertOk()
        ->assertJsonFragment(['positionName' => '社長']);
});

it('役職を削除できる', function () {
    [$org, $owner] = orgOwnerAndMember();
    $position = OrganizationPosition::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($owner)
        ->deleteJson("/api/organizations/{$org->id}/positions/{$position->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('organization_positions', ['id' => $position->id]);
});

it('一般メンバーは役職を作成できない', function () {
    [$org, , $member] = orgOwnerAndMember();

    $this->actingAs($member->user)
        ->postJson("/api/organizations/{$org->id}/positions", ['name' => 'X'])
        ->assertForbidden();
});

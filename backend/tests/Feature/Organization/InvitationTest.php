<?php

use App\Enums\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function makeOrgWithOwner(): array
{
    $owner = User::factory()->create();
    $org = Organization::factory()->create(['owner_user_id' => $owner->id]);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'role' => OrganizationRole::Owner,
    ]);

    return [$org, $owner];
}

it('管理者はメールでメンバーを招待しメールが送信される', function () {
    Mail::fake();
    [$org, $owner] = makeOrgWithOwner();

    $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])
        ->assertCreated()
        ->assertJsonPath('email', 'invitee@example.com')
        ->assertJsonPath('role', 'member');

    $this->assertDatabaseHas('organization_invitations', [
        'organization_id' => $org->id,
        'email' => 'invitee@example.com',
    ]);

    Mail::assertSent(OrganizationInvitationMail::class);
});

it('一般メンバーは招待できない', function () {
    [$org] = makeOrgWithOwner();
    $member = User::factory()->create();
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $member->id,
        'role' => OrganizationRole::Member,
    ]);

    $this->actingAs($member)
        ->postJson("/api/organizations/{$org->id}/invitations", [
            'email' => 'x@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();
});

it('オーナー権限での招待を拒否する', function () {
    [$org, $owner] = makeOrgWithOwner();

    $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/invitations", [
            'email' => 'x@example.com',
            'role' => 'owner',
        ])
        ->assertStatus(422);
});

it('招待されたユーザーは招待を受諾して参加できる（組織横断）', function () {
    Mail::fake();
    [$org, $owner] = makeOrgWithOwner();

    // Invitee belongs to a different (their own) organization.
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $org->id,
        'email' => 'invitee@example.com',
        'role' => OrganizationRole::Member,
        'invited_by_user_id' => $owner->id,
    ]);

    $this->actingAs($invitee)
        ->postJson("/api/invitations/{$invitation->token}/accept")
        ->assertOk()
        ->assertJsonPath('id', $org->id)
        ->assertJsonPath('role', 'member');

    $this->assertDatabaseHas('organization_members', [
        'organization_id' => $org->id,
        'user_id' => $invitee->id,
    ]);
    expect($invitation->fresh()->isAccepted())->toBeTrue();
});

it('別メール宛の招待の受諾を拒否する', function () {
    [$org, $owner] = makeOrgWithOwner();
    $other = User::factory()->create(['email' => 'someone-else@example.com']);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $org->id,
        'email' => 'invitee@example.com',
        'invited_by_user_id' => $owner->id,
    ]);

    $this->actingAs($other)
        ->postJson("/api/invitations/{$invitation->token}/accept")
        ->assertStatus(422);
});

it('期限切れの招待の受諾を拒否する', function () {
    [$org, $owner] = makeOrgWithOwner();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $invitation = OrganizationInvitation::factory()->expired()->create([
        'organization_id' => $org->id,
        'email' => 'invitee@example.com',
        'invited_by_user_id' => $owner->id,
    ]);

    $this->actingAs($invitee)
        ->postJson("/api/invitations/{$invitation->token}/accept")
        ->assertStatus(422);
});

it('不明な招待トークンは404を返す', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/invitations/nonexistent-token/accept')
        ->assertNotFound();
});

it('既にメンバーのユーザーの招待を拒否する', function () {
    Mail::fake();
    [$org, $owner] = makeOrgWithOwner();
    $member = User::factory()->create(['email' => 'member@example.com']);
    OrganizationMember::factory()->create([
        'organization_id' => $org->id,
        'user_id' => $member->id,
        'role' => OrganizationRole::Member,
    ]);

    $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('email');
});

it('同じメールへの再招待では保留中の招待を再利用する', function () {
    Mail::fake();
    [$org, $owner] = makeOrgWithOwner();

    OrganizationInvitation::factory()->create([
        'organization_id' => $org->id,
        'email' => 'invitee@example.com',
        'role' => OrganizationRole::Member,
        'invited_by_user_id' => $owner->id,
        'token' => 'old-token-value',
    ]);

    $this->actingAs($owner)
        ->postJson("/api/organizations/{$org->id}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'admin',
        ])
        ->assertCreated()
        ->assertJsonPath('role', 'admin');

    expect(OrganizationInvitation::where('organization_id', $org->id)
        ->where('email', 'invitee@example.com')->count())->toBe(1);
});

it('非メンバーはメンバー一覧を取得できない（テナント分離）', function () {
    [$org] = makeOrgWithOwner();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->getJson("/api/organizations/{$org->id}/members")
        ->assertForbidden();
});

it('メンバーは組織メンバー一覧を取得できる', function () {
    [$org, $owner] = makeOrgWithOwner();

    $this->actingAs($owner)
        ->getJson("/api/organizations/{$org->id}/members")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.role', 'owner');
});

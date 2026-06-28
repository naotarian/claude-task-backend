<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('登録時に確認メールを送信しユーザーは未認証のままにする', function () {
    Notification::fake();

    $this->postJson('/api/register', [
        'name' => '未認証太郎',
        'email' => 'unverified@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::where('email', 'unverified@example.com')->first();
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('メール認証が完了するまで業務APIはブロックされる', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->getJson('/api/organizations')
        ->assertForbidden();
});

it('認証済みユーザーは業務APIを利用できる', function () {
    $user = User::factory()->create(); // verified by default

    $this->actingAs($user)
        ->getJson('/api/organizations')
        ->assertOk();
});

it('未認証ユーザーにも /api/user は公開される', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('emailVerified', false);
});

it('署名付きリンクでメールを検証できる', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)
        ->getJson($url)
        ->assertOk()
        ->assertJsonPath('message', 'verified');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('検証には認証が必要（リダイレクトでなく401を返す）', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->getJson($url)->assertUnauthorized();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('確認メールを再送できる', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->postJson('/api/email/verification-notification')
        ->assertStatus(202);

    Notification::assertSentTo($user, VerifyEmail::class);
});

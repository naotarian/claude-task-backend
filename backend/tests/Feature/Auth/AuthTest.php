<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('新規ユーザーを登録してログイン状態にする', function () {
    $response = $this->postJson('/api/register', [
        'name' => '山田 太郎',
        'email' => 'taro@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('email', 'taro@example.com')
        ->assertJsonPath('name', '山田 太郎');

    $this->assertDatabaseHas('users', ['email' => 'taro@example.com']);
    $this->assertAuthenticated();
});

it('重複メールの登録を拒否する', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Dup',
        'email' => 'dup@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('正しい認証情報でログインできる', function () {
    User::factory()->create([
        'email' => 'hanako@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'hanako@example.com',
        'password' => 'secret123',
    ])->assertOk()->assertJsonPath('email', 'hanako@example.com');

    $this->assertAuthenticated();
});

it('不正な認証情報のログインを拒否する', function () {
    User::factory()->create([
        'email' => 'hanako@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'hanako@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422);

    $this->assertGuest();
});

it('/api/user で認証中のユーザーを返す', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});

it('未認証では /api/user を拒否する', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('ログアウトできる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/logout')
        ->assertOk();
});

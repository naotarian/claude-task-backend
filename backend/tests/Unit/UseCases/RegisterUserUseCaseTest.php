<?php

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\OrganizationProvisioner;
use App\UseCases\Auth\RegisterUserUseCase;
use Illuminate\Support\Facades\Hash;

it('パスワードをハッシュ化しユーザー作成と個人組織のプロビジョニングを行う', function () {
    $createdUser = new User(['name' => 'Taro', 'email' => 'taro@example.com']);

    $repo = Mockery::mock(UserRepositoryInterface::class);
    $repo->shouldReceive('create')
        ->once()
        ->withArgs(function (array $attributes) {
            expect($attributes['name'])->toBe('Taro');
            expect($attributes['email'])->toBe('taro@example.com');
            expect(Hash::check('password123', $attributes['password']))->toBeTrue();

            return true;
        })
        ->andReturn($createdUser);

    $provisioner = Mockery::mock(OrganizationProvisioner::class);
    $provisioner->shouldReceive('provision')
        ->once()
        ->withArgs(fn (string $name, OrganizationType $type, User $owner) => $type === OrganizationType::Personal && $owner === $createdUser)
        ->andReturn(new Organization);

    $useCase = new RegisterUserUseCase($repo, $provisioner);
    $user = $useCase->handle('Taro', 'taro@example.com', 'password123');

    expect($user)->toBe($createdUser);
});

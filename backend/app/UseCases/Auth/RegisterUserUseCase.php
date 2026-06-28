<?php

namespace App\UseCases\Auth;

use App\Enums\OrganizationType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\OrganizationProvisioner;
use App\UseCases\AbstractUseCase;
use Illuminate\Support\Facades\Hash;

class RegisterUserUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly OrganizationProvisioner $provisioner,
    ) {}

    /**
     * Register a user and auto-create their personal organization
     * (so freelancers can own projects and be the billing subject).
     */
    public function handle(string $name, string $email, string $password): User
    {
        return $this->transaction(function () use ($name, $email, $password): User {
            $user = $this->users->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->provisioner->provision(
                name: "{$name} (個人)",
                type: OrganizationType::Personal,
                owner: $user,
            );

            return $user;
        });
    }
}

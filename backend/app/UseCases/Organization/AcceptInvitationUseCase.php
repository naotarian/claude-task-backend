<?php

namespace App\UseCases\Organization;

use App\Models\Organization;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\UseCases\AbstractUseCase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AcceptInvitationUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
    ) {}

    public function handle(string $token, User $user): Organization
    {
        $invitation = $this->organizations->findInvitationByToken($token);

        if ($invitation === null) {
            throw new NotFoundHttpException('招待が見つかりません。');
        }

        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages(['token' => ['この招待は既に使用されています。']]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => ['この招待は有効期限が切れています。']]);
        }

        if (! hash_equals(strtolower($invitation->email), strtolower($user->email))) {
            throw ValidationException::withMessages(['token' => ['この招待は別のメールアドレス宛です。']]);
        }

        return $this->transaction(function () use ($invitation, $user): Organization {
            $organization = $invitation->organization;

            if (! $this->organizations->isMember($organization, $user)) {
                $this->organizations->addMember($organization, $user, $invitation->role);
            }

            $invitation->update(['accepted_at' => now()]);

            return $organization;
        });
    }
}

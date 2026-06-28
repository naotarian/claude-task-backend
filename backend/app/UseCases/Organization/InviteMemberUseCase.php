<?php

namespace App\UseCases\Organization;

use App\Enums\OrganizationRole;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\UseCases\AbstractUseCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMemberUseCase extends AbstractUseCase
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizations,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(Organization $organization, User $inviter, string $email, OrganizationRole $role): OrganizationInvitation
    {
        $existingUser = $this->users->findByEmail($email);

        if ($existingUser !== null && $this->organizations->isMember($organization, $existingUser)) {
            throw ValidationException::withMessages([
                'email' => ['このユーザーは既に組織のメンバーです。'],
            ]);
        }

        $invitation = $this->transaction(function () use ($organization, $inviter, $email, $role): OrganizationInvitation {
            $pending = $this->organizations->findPendingInvitation($organization, $email);

            if ($pending !== null) {
                $pending->update([
                    'role' => $role,
                    'token' => Str::random(64),
                    'invited_by_user_id' => $inviter->id,
                    'expires_at' => now()->addDays(7),
                ]);

                return $pending;
            }

            return $this->organizations->createInvitation([
                'organization_id' => $organization->id,
                'email' => $email,
                'role' => $role,
                'token' => Str::random(64),
                'invited_by_user_id' => $inviter->id,
                'expires_at' => now()->addDays(7),
            ]);
        });

        // Deliver the email only once the transaction has committed.
        $invitation->loadMissing('organization');
        Mail::to($email)->send(new OrganizationInvitationMail($invitation));

        return $invitation;
    }
}

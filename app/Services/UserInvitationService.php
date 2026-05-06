<?php

namespace App\Services;

use App\Enums\MailboxMessageType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Models\UserInvitation;
use App\Repositories\Contracts\UserInvitationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserInvitationService
{
    public function __construct(
        private UserInvitationRepositoryInterface $invitations,
        private UserManagementService $users,
        private NotificationService $notifications,
        private MailboxService $mailbox,
    ) {}

    public function listForManager(User $manager, int $perPage = 10): LengthAwarePaginator
    {
        return $this->invitations->latestForManager($manager, $perPage);
    }

    /**
     * @param  array{name: string, email: string, user_type: UserType|string, role_name?: string|null, department_id?: int|null, company_id?: int|null}  $attributes
     */
    public function invite(User $actor, array $attributes): UserInvitation
    {
        return DB::transaction(function () use ($actor, $attributes): UserInvitation {
            $user = $this->users->createUser($actor, [
                ...$attributes,
                'status' => UserStatus::Invited,
            ]);

            $invitation = $this->invitations->create([
                'company_id' => $user->company_id,
                'invited_by_id' => $actor->id,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'role_name' => $attributes['role_name'] ?? $user->user_type->value,
                'department_id' => $user->department_id,
                'token' => $this->newToken(),
                'expires_at' => now()->addDays(7),
            ]);

            $this->notifyInvitation($actor, $user, $invitation);
            activity()->performedOn($invitation)->causedBy($actor)->event('user.invited')->log('User invited');

            return $invitation;
        });
    }

    public function resend(User $actor, UserInvitation $invitation): UserInvitation
    {
        $updated = $this->invitations->update($invitation, [
            'token' => $this->newToken(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);

        $recipient = User::query()->where('email', $updated->email)->first();

        if ($recipient !== null) {
            $this->notifyInvitation($actor, $recipient, $updated);
        }

        activity()->performedOn($updated)->causedBy($actor)->event('user.invitation_resent')->log('User invitation resent');

        return $updated;
    }

    private function notifyInvitation(User $actor, User $recipient, UserInvitation $invitation): void
    {
        $this->notifications->notify(
            recipient: $recipient,
            type: 'user.invitation',
            title: 'Account invitation',
            body: 'You have been invited to the support desk.',
            link: route('dashboard'),
            company: $recipient->company,
        );

        $this->mailbox->send(
            recipient: $recipient,
            subject: 'Account invitation',
            body: "You were invited by {$actor->name}. Your invitation expires {$invitation->expires_at->toDateTimeString()}.",
            sender: $actor,
            type: MailboxMessageType::AdminNotice,
            relatedType: UserInvitation::class,
            relatedId: $invitation->id,
            companyId: $recipient->company_id,
        );
    }

    private function newToken(): string
    {
        return hash('sha256', Str::random(64));
    }
}

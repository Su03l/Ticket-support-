<?php

namespace App\Services;

use App\Events\TicketMentionCreated;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketMention;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TicketMentionService
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    /**
     * @return Collection<int, TicketMention>
     */
    public function createMentionsForComment(Ticket $ticket, TicketComment $comment, User $mentionedBy): Collection
    {
        $handles = $this->extractHandles($comment->body);

        if ($handles === []) {
            return collect();
        }

        return $this->findMentionedUsers($ticket, $handles)
            ->reject(fn (User $user): bool => $user->id === $mentionedBy->id)
            ->map(function (User $mentionedUser) use ($ticket, $comment, $mentionedBy): TicketMention {
                $mention = TicketMention::query()->firstOrCreate([
                    'comment_id' => $comment->id,
                    'mentioned_user_id' => $mentionedUser->id,
                ], [
                    'company_id' => $ticket->company_id,
                    'ticket_id' => $ticket->id,
                    'mentioned_by_id' => $mentionedBy->id,
                    'notified_at' => now(),
                ]);

                $this->notifications->notify(
                    recipient: $mentionedUser,
                    type: 'ticket.mention',
                    title: 'You were mentioned',
                    body: "{$mentionedBy->name} mentioned you on ticket {$ticket->ticket_number}.",
                    link: route('tickets.show', $ticket),
                    company: $ticket->company,
                );

                broadcast(new TicketMentionCreated($mention->load(['ticket', 'mentionedBy', 'mentionedUser'])))->toOthers();

                return $mention;
            });
    }

    /**
     * @return array<int, string>
     */
    public function extractHandles(string $body): array
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $handle): string => Str::lower($handle))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $handles
     * @return Collection<int, User>
     */
    private function findMentionedUsers(Ticket $ticket, array $handles): Collection
    {
        return User::query()
            ->where('company_id', $ticket->company_id)
            ->whereNotNull('department_id')
            ->get(['id', 'company_id', 'name', 'email'])
            ->filter(function (User $user) use ($handles): bool {
                $emailHandle = Str::lower(Str::before($user->email, '@'));
                $nameHandle = Str::lower(Str::of($user->name)->before(' ')->replaceMatches('/[^A-Za-z0-9._-]/', '')->toString());

                return in_array($emailHandle, $handles, true) || in_array($nameHandle, $handles, true);
            })
            ->values();
    }
}

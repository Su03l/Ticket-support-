<?php

namespace App\Repositories;

use App\Models\MailboxMessage;
use App\Models\User;
use App\Repositories\Contracts\MailboxRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MailboxRepository implements MailboxRepositoryInterface
{
    public function create(array $attributes): MailboxMessage
    {
        return MailboxMessage::create($attributes);
    }

    public function findForRecipient(User $recipient, int $id): ?MailboxMessage
    {
        return MailboxMessage::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->whereKey($id)
            ->first();
    }

    public function latestForRecipient(User $recipient, int $limit = 5): Collection
    {
        return MailboxMessage::query()
            ->with('sender:id,name,email')
            ->whereBelongsTo($recipient, 'recipient')
            ->whereNull('archived_at')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginatedForRecipient(User $recipient, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return MailboxMessage::query()
            ->with('sender:id,name,email')
            ->whereBelongsTo($recipient, 'recipient')
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at')->whereNull('archived_at'))
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at')->whereNull('archived_at'))
            ->when($status === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->when($status === null, fn ($query) => $query->whereNull('archived_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCountForRecipient(User $recipient): int
    {
        return MailboxMessage::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->count();
    }

    public function archiveForRecipient(User $recipient, MailboxMessage $message): MailboxMessage
    {
        if ($message->recipient_id !== $recipient->id) {
            abort(403);
        }

        $message->forceFill(['archived_at' => now()])->save();

        return $message->refresh();
    }

    public function deleteForRecipient(User $recipient, MailboxMessage $message): bool
    {
        if ($message->recipient_id !== $recipient->id) {
            return false;
        }

        return (bool) $message->delete();
    }
}

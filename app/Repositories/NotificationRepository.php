<?php

namespace App\Repositories;

use App\Models\SupportNotification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $attributes): SupportNotification
    {
        return SupportNotification::create($attributes);
    }

    public function findForRecipient(User $recipient, int $id): ?SupportNotification
    {
        return SupportNotification::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->whereKey($id)
            ->first();
    }

    public function latestForRecipient(User $recipient, int $limit = 5): Collection
    {
        return SupportNotification::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginatedForRecipient(User $recipient, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return SupportNotification::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCountForRecipient(User $recipient): int
    {
        return SupportNotification::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->whereNull('read_at')
            ->count();
    }

    public function markAllAsRead(User $recipient): int
    {
        return SupportNotification::query()
            ->whereBelongsTo($recipient, 'recipient')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteForRecipient(User $recipient, SupportNotification $notification): bool
    {
        if ($notification->recipient_id !== $recipient->id) {
            return false;
        }

        return (bool) $notification->delete();
    }
}

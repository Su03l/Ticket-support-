<?php

namespace App\Repositories\Contracts;

use App\Models\SupportNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SupportNotification;

    public function findForRecipient(User $recipient, int $id): ?SupportNotification;

    /**
     * @return Collection<int, SupportNotification>
     */
    public function latestForRecipient(User $recipient, int $limit = 5): Collection;

    public function paginatedForRecipient(User $recipient, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    public function unreadCountForRecipient(User $recipient): int;

    public function markAllAsRead(User $recipient): int;

    public function deleteForRecipient(User $recipient, SupportNotification $notification): bool;
}

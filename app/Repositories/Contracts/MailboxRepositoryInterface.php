<?php

namespace App\Repositories\Contracts;

use App\Models\MailboxMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MailboxRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MailboxMessage;

    public function findForRecipient(User $recipient, int $id): ?MailboxMessage;

    /**
     * @return Collection<int, MailboxMessage>
     */
    public function latestForRecipient(User $recipient, int $limit = 5): Collection;

    public function paginatedForRecipient(User $recipient, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    public function unreadCountForRecipient(User $recipient): int;

    public function archiveForRecipient(User $recipient, MailboxMessage $message): MailboxMessage;

    public function deleteForRecipient(User $recipient, MailboxMessage $message): bool;
}

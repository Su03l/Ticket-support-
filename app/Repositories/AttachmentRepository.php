<?php

namespace App\Repositories;

use App\Enums\AttachmentVisibility;
use App\Models\Attachment;
use App\Models\User;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    public function create(array $attributes): Attachment
    {
        return Attachment::query()->create($attributes);
    }

    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Attachment::query()
            ->with(['company:id,name', 'uploadedBy:id,name,email'])
            ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
            ->when($user->company_id === null && ! $user->can('files.view'), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($user->company_id !== null && ! $user->can('files.view'), function ($query) use ($user): void {
                $query->where('uploaded_by_id', $user->id)
                    ->where('visibility', AttachmentVisibility::Public);
            })
            ->when(($filters['module'] ?? null), fn ($query, string $module) => $query->where('attachable_type', $module))
            ->when(($filters['uploader_id'] ?? null), fn ($query, int|string $uploaderId) => $query->where('uploaded_by_id', $uploaderId))
            ->when(($filters['visibility'] ?? null), fn ($query, string $visibility) => $query->where('visibility', $visibility))
            ->latest()
            ->paginate($perPage);
    }
}

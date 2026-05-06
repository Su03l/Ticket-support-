<?php

use App\Enums\AttachmentVisibility;
use App\Models\Attachment;
use App\Models\ComplaintReply;
use App\Models\InquiryReply;
use App\Models\TicketComment;
use App\Models\TicketReply;
use App\Models\User;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use App\Services\AttachmentService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('File manager')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $module = '';

    public string $uploaderId = '';

    public string $visibility = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Attachment::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['module', 'uploaderId', 'visibility'], true)) {
            $this->resetPage();
        }
    }

    public function deleteAttachment(Attachment $attachment, AttachmentService $attachments): void
    {
        $this->authorize('delete', $attachment);

        $attachments->delete($attachment, Auth::user());

        Flux::toast(variant: 'success', text: __('File deleted.'));
    }

    public function with(AttachmentRepositoryInterface $attachments): array
    {
        $user = Auth::user();

        return [
            'attachments' => $attachments->paginatedForUser($user, [
                'module' => $this->module ?: null,
                'uploader_id' => $this->uploaderId ?: null,
                'visibility' => $this->visibility ?: null,
            ]),
            'modules' => [
                TicketReply::class => __('Ticket replies'),
                TicketComment::class => __('Ticket comments'),
                ComplaintReply::class => __('Complaint replies'),
                InquiryReply::class => __('Inquiry replies'),
            ],
            'visibilities' => AttachmentVisibility::cases(),
            'uploaders' => User::query()
                ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('File manager') }}</flux:heading>
        <flux:text>{{ __('Review, filter, download, and remove authorized attachments.') }}</flux:text>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <flux:select wire:model.live="module">
            <flux:select.option value="">{{ __('All modules') }}</flux:select.option>
            @foreach ($modules as $class => $label)
                <flux:select.option value="{{ $class }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="uploaderId">
            <flux:select.option value="">{{ __('All uploaders') }}</flux:select.option>
            @foreach ($uploaders as $uploader)
                <flux:select.option value="{{ $uploader->id }}">{{ $uploader->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="visibility">
            <flux:select.option value="">{{ __('All visibility') }}</flux:select.option>
            @foreach ($visibilities as $fileVisibility)
                <flux:select.option value="{{ $fileVisibility->value }}">{{ __(ucfirst($fileVisibility->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @forelse ($attachments as $attachment)
            <div wire:key="attachment-{{ $attachment->id }}" class="grid gap-3 border-b border-zinc-200 p-4 last:border-b-0 dark:border-zinc-800 lg:grid-cols-[1fr_9rem_10rem_10rem] lg:items-center">
                <div class="min-w-0">
                    <flux:heading size="sm" class="truncate">{{ $attachment->original_name }}</flux:heading>
                    <flux:text class="text-xs">{{ class_basename($attachment->attachable_type) }} - {{ number_format($attachment->size / 1024, 1) }} KB</flux:text>
                </div>

                <flux:badge size="sm">{{ __(ucfirst($attachment->visibility->value)) }}</flux:badge>
                <flux:text class="text-sm">{{ $attachment->uploadedBy?->name ?? __('Unknown') }}</flux:text>

                <div class="flex gap-2 lg:justify-end">
                    @can('download', $attachment)
                        <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('attachments.download', $attachment)">
                            {{ __('Download') }}
                        </flux:button>
                    @endcan

                    @can('delete', $attachment)
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteAttachment({{ $attachment->id }})">
                            {{ __('Delete') }}
                        </flux:button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="p-10 text-center">
                <flux:heading size="md">{{ __('No files found.') }}</flux:heading>
                <flux:text>{{ __('Attachments matching your filters will appear here.') }}</flux:text>
            </div>
        @endforelse
    </div>

    {{ $attachments->links() }}
</div>

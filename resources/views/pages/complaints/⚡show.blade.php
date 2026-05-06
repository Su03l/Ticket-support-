<?php

use App\Enums\ComplaintStatus;
use App\Enums\ReplyVisibility;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintReplyService;
use App\Services\ComplaintService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Complaint detail')] class extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Complaint $complaint;

    public string $replyBody = '';

    public bool $internalReply = false;

    public string $assigneeId = '';

    public string $status = '';

    public string $reason = '';

    public array $replyFiles = [];

    public function mount(Complaint $complaint): void
    {
        $this->authorize('view', $complaint);
        $this->complaint = $complaint->load($this->relations());
        $this->assigneeId = (string) ($complaint->assigned_to_id ?? '');
        $this->status = $complaint->status->value;
    }

    public function addReply(ComplaintReplyService $replies): void
    {
        $this->authorize('reply', $this->complaint);

        if ($this->internalReply) {
            $this->authorize('viewInternal', $this->complaint);
        }

        $validated = $this->validate([
            'replyBody' => ['required', 'string', 'min:2'],
            'replyFiles.*' => ['file', 'max:10240'],
        ]);

        $replies->addReply(
            $this->complaint,
            Auth::user(),
            $validated['replyBody'],
            $this->internalReply ? ReplyVisibility::Internal : ReplyVisibility::Public,
            $this->replyFiles,
        );

        $this->reset(['replyBody', 'replyFiles', 'internalReply']);
        $this->refreshComplaint();
    }

    public function assign(ComplaintService $complaints): void
    {
        $this->authorize('assign', $this->complaint);

        $validated = $this->validate([
            'assigneeId' => ['required', Rule::exists('users', 'id')->where('company_id', $this->complaint->company_id)],
        ]);

        $assignee = User::query()->findOrFail($validated['assigneeId']);
        $complaints->assignComplaint($this->complaint, Auth::user(), $assignee);
        $this->refreshComplaint();
    }

    public function updateStatus(ComplaintService $complaints): void
    {
        $this->authorize('close', $this->complaint);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = ComplaintStatus::from($validated['status']);

        if ($status === ComplaintStatus::Escalated) {
            $complaints->escalateComplaint($this->complaint, Auth::user(), $validated['reason'] ?: null);
        } else {
            $complaints->changeStatus($this->complaint, Auth::user(), $status, $validated['reason'] ?: null);
        }

        $this->reset('reason');
        $this->refreshComplaint();
    }

    public function with(): array
    {
        return [
            'agents' => User::query()
                ->where('company_id', $this->complaint->company_id)
                ->when($this->complaint->department_id !== null, fn ($query) => $query->where('department_id', $this->complaint->department_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => ComplaintStatus::cases(),
        ];
    }

    private function refreshComplaint(): void
    {
        $this->complaint = $this->complaint->refresh()->load($this->relations());
        $this->assigneeId = (string) ($this->complaint->assigned_to_id ?? '');
        $this->status = $this->complaint->status->value;
    }

    private function relations(): array
    {
        return [
            'department:id,name',
            'customer:id,name,email,user_type',
            'assignedAgent:id,name,email',
            'relatedTicket:id,ticket_number,title',
            'replies.user:id,name,email,user_type',
            'replies.attachments',
            'statusHistories.changedBy:id,name,email',
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:heading size="xl">{{ $complaint->complaint_number }}</flux:heading>
                <flux:badge color="blue">{{ __(str_replace('_', ' ', $complaint->status->value)) }}</flux:badge>
                <flux:badge color="{{ $complaint->severity === App\Enums\ComplaintSeverity::Critical ? 'red' : 'zinc' }}">{{ __($complaint->severity->value) }}</flux:badge>
            </div>
            <flux:text class="mt-1">{{ $complaint->title }}</flux:text>
            <flux:text class="mt-2 text-sm">
                {{ $complaint->department?->name ?? __('General') }} · {{ __('Customer') }}: {{ $complaint->customer->name }} · {{ __('Assignee') }}: {{ $complaint->assignedAgent?->name ?? __('Unassigned') }}
            </flux:text>
        </div>

        <flux:button variant="ghost" :href="route('complaints.index')" wire:navigate>{{ __('Back to complaints') }}</flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="flex flex-col gap-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Description') }}</flux:heading>
                <div class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $complaint->description }}</div>
                @if ($complaint->relatedTicket)
                    <flux:text class="mt-4 text-sm">
                        {{ __('Related ticket') }}:
                        <a href="{{ route('tickets.show', $complaint->relatedTicket) }}" wire:navigate class="font-medium text-blue-600 dark:text-blue-400">{{ $complaint->relatedTicket->ticket_number }}</a>
                    </flux:text>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Replies') }}</flux:heading>
                <div class="mt-4 flex flex-col gap-4">
                    @foreach ($complaint->replies as $reply)
                        @continue($reply->visibility === App\Enums\ReplyVisibility::Internal && ! Auth::user()->can('viewInternal', $complaint))
                        <div wire:key="complaint-reply-{{ $reply->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <flux:text class="font-medium">{{ $reply->user->name }}</flux:text>
                                    @if ($reply->visibility === App\Enums\ReplyVisibility::Internal)
                                        <flux:badge size="sm">{{ __('Internal') }}</flux:badge>
                                    @endif
                                </div>
                                <flux:text class="text-xs">{{ $reply->created_at->diffForHumans() }}</flux:text>
                            </div>
                            <div class="mt-2 whitespace-pre-line text-sm">{{ $reply->body }}</div>
                            @if ($reply->attachments->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($reply->attachments as $attachment)
                                        @can('view', $attachment)
                                            <flux:button size="xs" variant="ghost" :href="route('attachments.download', $attachment)" wire:key="complaint-attachment-{{ $attachment->id }}">
                                                {{ $attachment->original_name }}
                                            </flux:button>
                                        @endcan
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @can('reply', $complaint)
                    <form wire:submit="addReply" class="mt-5 flex flex-col gap-3">
                        <flux:textarea wire:model="replyBody" :label="__('Add reply')" rows="4" />
                        @can('viewInternal', $complaint)
                            <flux:checkbox wire:model="internalReply" :label="__('Internal reply')" />
                        @endcan
                        <flux:input type="file" wire:model="replyFiles" multiple :label="__('Attachments')" />
                        <flux:button type="submit" variant="primary" class="self-end">{{ __('Send reply') }}</flux:button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="flex flex-col gap-4">
            @can('assign', $complaint)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="sm">{{ __('Assignment') }}</flux:heading>
                    <div class="mt-3 flex flex-col gap-3">
                        <flux:select wire:model="assigneeId" :label="__('Agent')">
                            <flux:select.option value="">{{ __('Select agent') }}</flux:select.option>
                            @foreach ($agents as $agent)
                                <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button wire:click="assign" variant="primary">{{ __('Assign') }}</flux:button>
                    </div>
                </div>
            @endcan

            @can('close', $complaint)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="sm">{{ __('Status') }}</flux:heading>
                    <div class="mt-3 flex flex-col gap-3">
                        <flux:select wire:model="status" :label="__('Status')">
                            @foreach ($statuses as $complaintStatus)
                                <flux:select.option value="{{ $complaintStatus->value }}">{{ __(str_replace('_', ' ', $complaintStatus->value)) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="reason" :label="__('Reason')" rows="3" />
                        <flux:button wire:click="updateStatus" variant="primary">{{ __('Update status') }}</flux:button>
                    </div>
                </div>
            @endcan

            @can('viewInternal', $complaint)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="sm">{{ __('History') }}</flux:heading>
                    <div class="mt-3 flex flex-col gap-3 text-sm">
                        @foreach ($complaint->statusHistories as $history)
                            <div wire:key="complaint-history-{{ $history->id }}" class="border-b border-zinc-200 pb-2 last:border-b-0 dark:border-zinc-800">
                                {{ $history->changedBy->name }}:
                                {{ $history->old_status ? __(str_replace('_', ' ', $history->old_status->value)) : __('None') }}
                                &rarr;
                                {{ __(str_replace('_', ' ', $history->new_status->value)) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>

<?php

use App\Enums\InquiryStatus;
use App\Enums\ReplyVisibility;
use App\Models\Inquiry;
use App\Models\TicketPriority;
use App\Models\User;
use App\Services\InquiryReplyService;
use App\Services\InquiryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Inquiry detail')] class extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Inquiry $inquiry;
    public string $replyBody = '';
    public bool $internalReply = false;
    public string $assigneeId = '';
    public string $priorityId = '';
    public string $status = '';
    public string $reason = '';
    public array $replyFiles = [];

    public function mount(Inquiry $inquiry): void
    {
        $this->authorize('view', $inquiry);
        $this->inquiry = $inquiry->load($this->relations());
        $this->assigneeId = (string) ($inquiry->assigned_to_id ?? '');
        $this->status = $inquiry->status->value;
    }

    public function addReply(InquiryReplyService $replies): void
    {
        $this->authorize('reply', $this->inquiry);

        if ($this->internalReply) {
            $this->authorize('viewInternal', $this->inquiry);
        }

        $validated = $this->validate([
            'replyBody' => ['required', 'string', 'min:2'],
            'replyFiles.*' => ['file', 'max:10240'],
        ]);

        $replies->addReply($this->inquiry, Auth::user(), $validated['replyBody'], $this->internalReply ? ReplyVisibility::Internal : ReplyVisibility::Public, $this->replyFiles);

        $this->reset(['replyBody', 'replyFiles', 'internalReply']);
        $this->refreshInquiry();
    }

    public function assign(InquiryService $inquiries): void
    {
        $this->authorize('reply', $this->inquiry);

        $validated = $this->validate([
            'assigneeId' => ['required', Rule::exists('users', 'id')->where('company_id', $this->inquiry->company_id)],
        ]);

        $inquiries->assignInquiry($this->inquiry, Auth::user(), User::query()->findOrFail($validated['assigneeId']));
        $this->refreshInquiry();
    }

    public function updateStatus(InquiryService $inquiries): void
    {
        $this->authorize('close', $this->inquiry);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $inquiries->changeStatus($this->inquiry, Auth::user(), InquiryStatus::from($validated['status']), $validated['reason'] ?: null);
        $this->reset('reason');
        $this->refreshInquiry();
    }

    public function convert(InquiryService $inquiries): void
    {
        $this->authorize('convert', $this->inquiry);

        $ticket = $inquiries->convertToTicket($this->inquiry, Auth::user(), $this->priorityId ? (int) $this->priorityId : null);

        $this->redirectRoute('tickets.show', $ticket, navigate: true);
    }

    public function with(): array
    {
        return [
            'agents' => User::query()->where('company_id', $this->inquiry->company_id)->when($this->inquiry->department_id !== null, fn ($query) => $query->where('department_id', $this->inquiry->department_id))->orderBy('name')->get(['id', 'name']),
            'statuses' => InquiryStatus::cases(),
            'priorities' => TicketPriority::query()->where('company_id', $this->inquiry->company_id)->where('is_active', true)->orderBy('level')->get(['id', 'name']),
        ];
    }

    private function refreshInquiry(): void
    {
        $this->inquiry = $this->inquiry->refresh()->load($this->relations());
        $this->assigneeId = (string) ($this->inquiry->assigned_to_id ?? '');
        $this->status = $this->inquiry->status->value;
    }

    private function relations(): array
    {
        return ['department:id,name', 'customer:id,name,email,user_type', 'assignedAgent:id,name,email', 'convertedTicket:id,ticket_number,title', 'replies.user:id,name,email,user_type', 'replies.attachments', 'statusHistories.changedBy:id,name,email', 'slaRecord'];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:heading size="xl">{{ $inquiry->inquiry_number }}</flux:heading>
                <flux:badge color="blue">{{ __(str_replace('_', ' ', $inquiry->status->value)) }}</flux:badge>
                @if ($inquiry->slaRecord?->status?->value === 'breached')
                    <flux:badge color="red">{{ __('SLA breached') }}</flux:badge>
                @endif
            </div>
            <flux:text class="mt-1">{{ $inquiry->subject }}</flux:text>
            <flux:text class="mt-2 text-sm">{{ $inquiry->department?->name ?? __('General') }} · {{ __('Customer') }}: {{ $inquiry->customer->name }} · {{ __('Assignee') }}: {{ $inquiry->assignedAgent?->name ?? __('Unassigned') }}</flux:text>
        </div>
        <flux:button variant="ghost" :href="route('inquiries.index')" wire:navigate>{{ __('Back to inquiries') }}</flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="flex flex-col gap-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Inquiry') }}</flux:heading>
                <div class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $inquiry->body }}</div>
                @if ($inquiry->convertedTicket)
                    <flux:text class="mt-4 text-sm">{{ __('Converted ticket') }}: <a class="font-medium text-blue-600 dark:text-blue-400" href="{{ route('tickets.show', $inquiry->convertedTicket) }}" wire:navigate>{{ $inquiry->convertedTicket->ticket_number }}</a></flux:text>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Replies') }}</flux:heading>
                <div class="mt-4 flex flex-col gap-4">
                    @foreach ($inquiry->replies as $reply)
                        @continue($reply->visibility === App\Enums\ReplyVisibility::Internal && ! Auth::user()->can('viewInternal', $inquiry))
                        <div wire:key="inquiry-reply-{{ $reply->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <flux:text class="font-medium">{{ $reply->user->name }}</flux:text>
                                <flux:text class="text-xs">{{ $reply->created_at->diffForHumans() }}</flux:text>
                            </div>
                            <div class="mt-2 whitespace-pre-line text-sm">{{ $reply->body }}</div>
                            @if ($reply->attachments->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($reply->attachments as $attachment)
                                        @can('view', $attachment)
                                            <flux:button size="xs" variant="ghost" :href="route('attachments.download', $attachment)" wire:key="inquiry-attachment-{{ $attachment->id }}">{{ $attachment->original_name }}</flux:button>
                                        @endcan
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @can('reply', $inquiry)
                    <form wire:submit="addReply" class="mt-5 flex flex-col gap-3">
                        <flux:textarea wire:model="replyBody" :label="__('Add reply')" rows="4" />
                        @can('viewInternal', $inquiry)
                            <flux:checkbox wire:model="internalReply" :label="__('Internal reply')" />
                        @endcan
                        <flux:input type="file" wire:model="replyFiles" multiple :label="__('Attachments')" />
                        <flux:button type="submit" variant="primary" class="self-end">{{ __('Send reply') }}</flux:button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="flex flex-col gap-4">
            @can('reply', $inquiry)
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

                @can('convert', $inquiry)
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:heading size="sm">{{ __('Convert to ticket') }}</flux:heading>
                        <div class="mt-3 flex flex-col gap-3">
                            <flux:select wire:model="priorityId" :label="__('Priority')">
                                <flux:select.option value="">{{ __('Optional') }}</flux:select.option>
                                @foreach ($priorities as $priority)
                                    <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button wire:click="convert" variant="primary">{{ __('Convert') }}</flux:button>
                        </div>
                    </div>
                @endcan
            @endcan

            @can('close', $inquiry)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <flux:heading size="sm">{{ __('Status') }}</flux:heading>
                    <div class="mt-3 flex flex-col gap-3">
                        <flux:select wire:model="status" :label="__('Status')">
                            @foreach ($statuses as $inquiryStatus)
                                <flux:select.option value="{{ $inquiryStatus->value }}">{{ __(str_replace('_', ' ', $inquiryStatus->value)) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="reason" :label="__('Reason')" rows="3" />
                        <flux:button wire:click="updateStatus" variant="primary">{{ __('Update status') }}</flux:button>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>

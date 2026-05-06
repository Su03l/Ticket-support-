<?php

use App\Enums\ReplyVisibility;
use App\Enums\TicketPresenceAction;
use App\Enums\TicketStatus;
use App\Models\CannedResponse;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use App\Services\CannedResponseService;
use App\Services\TicketCommentService;
use App\Services\TicketPresenceService;
use App\Services\TicketRatingService;
use App\Services\TicketReplyService;
use App\Services\TicketService;
use App\Services\TicketTimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Ticket detail')] class extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Ticket $ticket;

    public string $replyBody = '';

    public string $commentBody = '';

    public string $assigneeId = '';

    public string $transferDepartmentId = '';

    public string $status = '';

    public string $reason = '';

    public string $rating = '';

    public string $feedback = '';

    public string $cannedResponseId = '';

    public array $replyFiles = [];

    public array $commentFiles = [];

    public function mount(Ticket $ticket, TicketPresenceService $presence): void
    {
        $this->authorize('view', $ticket);
        $this->ticket = $ticket->load($this->relations());
        $this->assigneeId = (string) ($ticket->assigned_to_id ?? '');
        $this->transferDepartmentId = (string) $ticket->department_id;
        $this->status = $ticket->status->value;
        $presence->touch($ticket, Auth::user(), TicketPresenceAction::Viewing);
    }

    public function addReply(TicketReplyService $replies): void
    {
        $this->authorize('reply', $this->ticket);

        $validated = $this->validate([
            'replyBody'    => ['required', 'string', 'min:2'],
            'replyFiles.*' => ['file', 'max:10240'],
        ]);

        $replies->addReply($this->ticket, Auth::user(), $validated['replyBody'], ReplyVisibility::Public, $this->replyFiles);

        $this->reset(['replyBody', 'replyFiles']);
        $this->refreshTicket();
    }

    public function updatePresence(string $action, TicketPresenceService $presence): void
    {
        $this->authorize('view', $this->ticket);
        $presence->touch($this->ticket, Auth::user(), TicketPresenceAction::from($action));
    }

    public function startTimer(TicketTimeTrackingService $timeTracking): void
    {
        $this->authorize('reply', $this->ticket);
        $timeTracking->start($this->ticket, Auth::user());
    }

    public function stopTimer(TicketTimeTrackingService $timeTracking): void
    {
        $this->authorize('reply', $this->ticket);
        $timeTracking->stop($this->ticket, Auth::user());
    }

    public function useCannedResponse(): void
    {
        $response = CannedResponse::query()
            ->where('company_id', $this->ticket->company_id)
            ->where('is_active', true)
            ->findOrFail($this->cannedResponseId);

        $this->replyBody = trim($this->replyBody . "\n\n" . $response->body);
    }

    public function submitRating(TicketRatingService $ratings): void
    {
        $this->authorize('create', [TicketRating::class, $this->ticket]);

        $validated = $this->validate([
            'rating'   => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $ratings->rate($this->ticket, Auth::user(), (int) $validated['rating'], $validated['feedback'] ?: null);
        $this->reset(['rating', 'feedback']);
        $this->refreshTicket();
    }

    public function addComment(TicketCommentService $comments): void
    {
        $this->authorize('comment', $this->ticket);

        $validated = $this->validate([
            'commentBody'    => ['required', 'string', 'min:2'],
            'commentFiles.*' => ['file', 'max:10240'],
        ]);

        $comments->addComment($this->ticket, Auth::user(), $validated['commentBody'], $this->commentFiles);

        $this->reset(['commentBody', 'commentFiles']);
        $this->refreshTicket();
    }

    public function assign(TicketService $tickets): void
    {
        $this->authorize('assign', $this->ticket);

        $validated = $this->validate([
            'assigneeId' => ['required', Rule::exists('users', 'id')->where('company_id', $this->ticket->company_id)],
        ]);

        $assignee = User::query()->findOrFail($validated['assigneeId']);
        $tickets->assignTicket($this->ticket, Auth::user(), $assignee, $this->reason ?: null);
        $this->reset('reason');
        $this->refreshTicket();
    }

    public function transfer(TicketService $tickets): void
    {
        $this->authorize('transfer', $this->ticket);

        $validated = $this->validate([
            'transferDepartmentId' => ['required', Rule::exists('departments', 'id')->where('company_id', $this->ticket->company_id)],
            'reason'               => ['required', 'string', 'min:3'],
        ]);

        $department = Department::query()->findOrFail($validated['transferDepartmentId']);
        $tickets->transferTicket($this->ticket, Auth::user(), $department, $validated['reason']);
        $this->reset('reason');
        $this->refreshTicket();
    }

    public function changeStatus(TicketService $tickets): void
    {
        $this->authorize(in_array($this->status, [TicketStatus::Closed->value, TicketStatus::Cancelled->value], true) ? 'close' : 'reopen', $this->ticket);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $tickets->changeStatus($this->ticket, Auth::user(), TicketStatus::from($validated['status']), $validated['reason'] ?: null);
        $this->reset('reason');
        $this->refreshTicket();
    }

    public function with(): array
    {
        $presence    = app(TicketPresenceService::class);
        $timeTracking = app(TicketTimeTrackingService::class);

        $timeline = collect();
        foreach ($this->ticket->replies as $reply) {
            if ($reply->visibility === App\Enums\ReplyVisibility::Internal && ! Auth::user()->can('tickets.comment')) {
                continue;
            }
            $timeline->push(['type' => 'reply', 'model' => $reply, 'date' => $reply->created_at]);
        }
        if (Auth::user()->can('tickets.comment')) {
            foreach ($this->ticket->comments as $comment) {
                $timeline->push(['type' => 'comment', 'model' => $comment, 'date' => $comment->created_at]);
            }
        }
        foreach ($this->ticket->statusHistories as $history) {
            $timeline->push(['type' => 'status', 'model' => $history, 'date' => $history->created_at]);
        }
        $timeline = $timeline->sortBy('date')->values();

        return [
            'agents' => User::query()
                ->where('company_id', $this->ticket->company_id)
                ->where('department_id', $this->ticket->department_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'departments' => Department::query()
                ->where('company_id', $this->ticket->company_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses'          => TicketStatus::cases(),
            'cannedResponses'   => app(CannedResponseService::class)->activeForUser(Auth::user()),
            'activeColleagues'  => $presence->activeColleagues($this->ticket, Auth::user()),
            'runningTimer'      => $timeTracking->runningEntry($this->ticket, Auth::user()),
            'trackedSeconds'    => $timeTracking->secondsForTicket($this->ticket, Auth::user()),
            'timeline'          => $timeline,
        ];
    }

    private function refreshTicket(): void
    {
        $this->ticket = $this->ticket->refresh()->load($this->relations());
        $this->assigneeId = (string) ($this->ticket->assigned_to_id ?? '');
        $this->transferDepartmentId = (string) $this->ticket->department_id;
        $this->status = $this->ticket->status->value;
    }

    private function relations(): array
    {
        return [
            'department:id,name',
            'customer:id,name,email,user_type',
            'assignedAgent:id,name,email',
            'priority:id,name,color',
            'category:id,name',
            'replies.user:id,name,email,user_type',
            'replies.attachments',
            'comments.user:id,name,email',
            'comments.attachments',
            'assignments.assignedBy:id,name,email',
            'assignments.assignedTo:id,name,email',
            'assignments.fromUser:id,name,email',
            'transfers.transferredBy:id,name,email',
            'transfers.fromDepartment:id,name',
            'transfers.toDepartment:id,name',
            'statusHistories.changedBy:id,name,email',
            'rating.customer:id,name,email',
        ];
    }
}; ?>

<div class="flex flex-col gap-6" wire:poll.30s>
    {{-- Ticket header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $ticket->ticket_number }}</h1>
                <x-status-badge :status="$ticket->status->value" />
                @if ($ticket->priority)
                    <x-status-badge :status="$ticket->priority->name" />
                @endif
            </div>
            <p class="mt-1.5 text-base font-medium text-zinc-700 dark:text-zinc-300">{{ $ticket->title }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                <span class="font-medium">{{ $ticket->department->name }}</span>
                <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                {{ __('Customer') }}: <span class="font-medium">{{ $ticket->customer->name }}</span>
                <span class="mx-1.5 text-zinc-300 dark:text-zinc-600">·</span>
                {{ __('Assignee') }}: <span class="font-medium">{{ $ticket->assignedAgent?->name ?? __('Unassigned') }}</span>
            </p>
        </div>

        <flux:button variant="ghost" icon="arrow-left" :href="route('tickets.index')" wire:navigate class="shrink-0">
            {{ __('Back') }}
        </flux:button>
    </div>

    {{-- Active colleagues notice --}}
    @if ($activeColleagues->isNotEmpty())
        <div class="flex items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/30">
            <flux:icon name="eye" class="mt-0.5 size-4 shrink-0 text-amber-500" />
            <div class="space-y-1">
                @foreach ($activeColleagues as $presence)
                    <p wire:key="presence-{{ $presence->id }}" class="text-sm text-amber-800 dark:text-amber-200">
                        {{ __('Your colleague :name is :action this ticket now.', ['name' => '<strong>' . e($presence->user->name) . '</strong>', 'action' => __(str_replace('_', ' ', $presence->action->value))]) }}
                    </p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Time tracking --}}
    @can('reply', $ticket)
        <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="clock" class="size-4 text-zinc-500 dark:text-zinc-400" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Time tracking') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Tracked on this ticket') }}: <span class="font-mono font-semibold">{{ gmdate('H:i:s', $trackedSeconds) }}</span></p>
                </div>
            </div>
            <div>
                @if ($runningTimer)
                    <flux:button wire:click="stopTimer" icon="stop" variant="danger" size="sm">{{ __('Stop timer') }}</flux:button>
                @else
                    <flux:button wire:click="startTimer" icon="play" variant="primary" size="sm">{{ __('Start timer') }}</flux:button>
                @endif
            </div>
        </div>
    @endcan

    {{-- Main two-column layout --}}
    <div class="grid gap-6 xl:grid-cols-[1fr_21rem]">
        {{-- Left: description + timeline + reply forms --}}
        <div class="flex flex-col gap-5">
            {{-- Description --}}
            <x-section-card :heading="__('Description')" icon="document-text">
                <div class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $ticket->description }}</div>
            </x-section-card>

            {{-- Timeline --}}
            <x-section-card :heading="__('Ticket timeline')" icon="clock">
                <div class="flex flex-col gap-4">
                    @forelse ($timeline as $item)
                        @if ($item['type'] === 'reply')
                            {{-- Reply bubble --}}
                            <div wire:key="timeline-{{ $item['type'] }}-{{ $item['model']->id }}" class="flex gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                                    {{ mb_substr($item['model']->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-[0_1px_3px_0_rgb(0,0,0,0.03)] dark:border-zinc-800/80 dark:bg-zinc-900">
                                        <div class="mb-2.5 flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $item['model']->user->name }}</span>
                                            <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{ $item['date']->diffForHumans() }}</span>
                                        </div>
                                        <div class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $item['model']->body }}</div>
                                        @if ($item['model']->attachments->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-2 border-t border-zinc-100/80 pt-3 dark:border-zinc-800">
                                                @foreach ($item['model']->attachments as $attachment)
                                                    @can('view', $attachment)
                                                        <flux:button size="xs" variant="ghost" icon="paper-clip" :href="route('attachments.download', $attachment)" wire:key="reply-attachment-{{ $attachment->id }}">
                                                            {{ $attachment->original_name }}
                                                        </flux:button>
                                                    @endcan
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        @elseif ($item['type'] === 'comment')
                            {{-- Internal comment --}}
                            <div wire:key="timeline-{{ $item['type'] }}-{{ $item['model']->id }}" class="flex gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50">
                                    <flux:icon name="lock-closed" class="size-4 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="rounded-xl border border-amber-200/80 bg-amber-50/80 p-4 dark:border-amber-900/30 dark:bg-amber-950/20">
                                        <div class="mb-2.5 flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ $item['model']->user->name }}</span>
                                                <flux:badge size="sm" color="amber">{{ __('Internal') }}</flux:badge>
                                            </div>
                                            <span class="text-xs font-medium text-amber-600/70 dark:text-amber-500/70">{{ $item['date']->diffForHumans() }}</span>
                                        </div>
                                        <div class="whitespace-pre-line text-sm leading-relaxed text-amber-900 dark:text-amber-100">{{ $item['model']->body }}</div>
                                        @if ($item['model']->attachments->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-2 border-t border-amber-200/60 pt-3 dark:border-amber-900/30">
                                                @foreach ($item['model']->attachments as $attachment)
                                                    @can('view', $attachment)
                                                        <flux:button size="xs" variant="ghost" icon="paper-clip" :href="route('attachments.download', $attachment)" wire:key="comment-attachment-{{ $attachment->id }}" class="text-amber-700 dark:text-amber-400">
                                                            {{ $attachment->original_name }}
                                                        </flux:button>
                                                    @endcan
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        @elseif ($item['type'] === 'status')
                            {{-- Status change event --}}
                            <div wire:key="timeline-{{ $item['type'] }}-{{ $item['model']->id }}" class="flex items-center gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="arrow-path" class="size-4 text-zinc-500 dark:text-zinc-400" />
                                </div>
                                <div class="flex flex-1 flex-wrap items-center gap-1 text-sm">
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $item['model']->changedBy->name ?? __('System') }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('changed status to') }}</span>
                                    <x-status-badge :status="$item['model']->new_status->value" />
                                    <span class="ml-auto text-xs text-zinc-400 dark:text-zinc-500">{{ $item['date']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endif
                    @empty
                        <x-empty-state icon="inbox" :heading="__('No activity yet.')" class="py-10" />
                    @endforelse
                </div>

                {{-- Reply form --}}
                @can('reply', $ticket)
                    <div class="mt-6 border-t border-zinc-100/80 pt-6 dark:border-zinc-800">
                        <p class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Add a reply') }}</p>
                        <form wire:submit="addReply" class="flex flex-col gap-4">
                            @if ($cannedResponses->isNotEmpty())
                                <div class="flex gap-2">
                                    <flux:select wire:model="cannedResponseId" :label="__('Template')" class="flex-1">
                                        <flux:select.option value="">{{ __('Select canned response') }}</flux:select.option>
                                        @foreach ($cannedResponses as $response)
                                            <flux:select.option value="{{ $response->id }}">{{ $response->title }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button type="button" wire:click="useCannedResponse" class="self-end" variant="ghost">{{ __('Insert') }}</flux:button>
                                </div>
                            @endif
                            <flux:textarea
                                wire:model="replyBody"
                                x-on:focus="$wire.updatePresence('replying')"
                                :label="__('Message')"
                                rows="4"
                                placeholder="{{ __('Type your reply here...') }}"
                            />
                            <flux:input type="file" wire:model="replyFiles" multiple :label="__('Attachments')" />
                            <div class="flex justify-end">
                                <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Send reply') }}</flux:button>
                            </div>
                        </form>
                    </div>
                @endcan

                {{-- Internal note form --}}
                @can('comment', $ticket)
                    <div class="mt-6 border-t border-zinc-100/80 pt-6 dark:border-zinc-800">
                        <p class="mb-4 text-sm font-semibold text-amber-600 dark:text-amber-500">{{ __('Add internal note') }}</p>
                        <form wire:submit="addComment" class="flex flex-col gap-4 rounded-xl border border-amber-100 bg-amber-50/60 p-5 dark:border-amber-900/30 dark:bg-amber-950/10">
                            <flux:textarea
                                wire:model="commentBody"
                                x-on:focus="$wire.updatePresence('commenting')"
                                :label="__('Internal note')"
                                rows="3"
                                placeholder="{{ __('Only visible to staff...') }}"
                            />
                            <flux:input type="file" wire:model="commentFiles" multiple :label="__('Internal attachments')" />
                            <div class="flex justify-end">
                                <flux:button type="submit" class="bg-amber-600 text-white hover:bg-amber-700 border-none">{{ __('Save internal note') }}</flux:button>
                            </div>
                        </form>
                    </div>
                @endcan
            </x-section-card>
        </div>

        {{-- Right sidebar panels --}}
        <div class="flex flex-col gap-4">
            @can('assign', $ticket)
                <x-section-card :heading="__('Assignment')" icon="user-plus" compact>
                    <div class="flex flex-col gap-3">
                        <flux:select wire:model="assigneeId" :label="__('Agent')">
                            <flux:select.option value="">{{ __('Select agent') }}</flux:select.option>
                            @foreach ($agents as $agent)
                                <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button wire:click="assign" variant="primary" class="w-full">{{ __('Assign') }}</flux:button>
                    </div>
                </x-section-card>
            @endcan

            @can('transfer', $ticket)
                <x-section-card :heading="__('Transfer')" icon="arrow-right-circle" compact>
                    <div class="flex flex-col gap-3">
                        <flux:select wire:model="transferDepartmentId" :label="__('Department')">
                            @foreach ($departments as $department)
                                <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="reason" :label="__('Reason')" rows="2" />
                        <flux:button wire:click="transfer" variant="primary" class="w-full">{{ __('Transfer') }}</flux:button>
                    </div>
                </x-section-card>
            @endcan

            @if (Auth::user()->user_type !== \App\Enums\UserType::Customer)
                <x-section-card :heading="__('Status')" icon="tag" compact>
                    <div class="flex flex-col gap-3">
                        <flux:select wire:model="status" :label="__('Status')">
                            @foreach ($statuses as $ticketStatus)
                                <flux:select.option value="{{ $ticketStatus->value }}">{{ __(str_replace('_', ' ', $ticketStatus->value)) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="reason" :label="__('Reason')" rows="2" />
                        <flux:button wire:click="changeStatus" variant="primary" class="w-full">{{ __('Update status') }}</flux:button>
                    </div>
                </x-section-card>
            @endif

            {{-- Rating --}}
            @if ($ticket->rating)
                <x-section-card :heading="__('Customer rating')" icon="star" compact>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/30">
                            <flux:icon name="star" class="size-5 text-amber-500" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ $ticket->rating->rating }}<span class="text-sm font-medium text-zinc-400">/5</span></p>
                            @if ($ticket->rating->feedback)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket->rating->feedback }}</p>
                            @endif
                        </div>
                    </div>
                </x-section-card>
            @else
                @can('create', [\App\Models\TicketRating::class, $ticket])
                    <x-section-card :heading="__('Rate this ticket')" icon="star" compact>
                        <form wire:submit="submitRating" class="flex flex-col gap-3">
                            <flux:input type="number" min="1" max="5" wire:model="rating" :label="__('Rating (1–5)')" />
                            <flux:textarea wire:model="feedback" :label="__('Feedback')" rows="2" />
                            <flux:button type="submit" variant="primary" class="w-full">{{ __('Submit rating') }}</flux:button>
                        </form>
                    </x-section-card>
                @endcan
            @endif
        </div>
    </div>
</div>

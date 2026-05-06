<?php

use App\Services\ActivityLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Activity logs')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $module = '';
    public string $event = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Activity::class);
    }

    public function with(ActivityLogService $activityLogs): array
    {
        return ['logs' => $activityLogs->logsForUser(Auth::user(), ['module' => $this->module ?: null, 'event' => $this->event ?: null])];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Activity logs') }}</flux:heading>
        <flux:text>{{ __('Read-only audit trail for important business actions.') }}</flux:text>
    </div>
    <div class="grid gap-3 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="module" :placeholder="__('Module')" />
        <flux:input wire:model.live.debounce.300ms="event" :placeholder="__('Event')" />
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @forelse ($logs as $log)
            <div wire:key="activity-{{ $log->id }}" class="border-b border-zinc-200 p-4 last:border-b-0 dark:border-zinc-800">
                <flux:heading size="sm">{{ $log->event ?? __('Activity') }}</flux:heading>
                <flux:text>{{ $log->description }}</flux:text>
                <flux:text class="text-xs">{{ $log->causer?->name ?? __('System') }} · {{ $log->created_at->diffForHumans() }}</flux:text>
            </div>
        @empty
            <div class="p-10 text-center"><flux:text>{{ __('No activity logs found.') }}</flux:text></div>
        @endforelse
    </div>
    {{ $logs->links() }}
</div>

<?php

use App\Services\ActivityLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Activity Logs')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 #[Url(history: true)]
 public string $module = '';

 #[Url(history: true)]
 public string $event = '';

 public function mount(): void
 {
  $this->authorize('viewAny', Activity::class);
 }

 public function updatedModule(): void
 {
  $this->resetPage();
 }

 public function updatedEvent(): void
 {
  $this->resetPage();
 }

 public function with(ActivityLogService $activityLogs): array
 {
  return ['logs' => $activityLogs->logsForUser(Auth::user(), ['module' => $this->module ?: null, 'event' => $this->event ?: null])];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div>
   <flux:heading size="xl"level="1">{{ __('Audit Trail') }}</flux:heading>
   <flux:text variant="subtle" class="mt-1">{{ __('A chronological ledger of significant system interactions and business logic executions.') }}</flux:text>
  </div>

  <div class="flex items-center gap-2">
   <flux:input wire:model.live.debounce.300ms="module" icon="cube":placeholder="__('Module...')" class="w-full sm:w-48"/>
   <flux:input wire:model.live.debounce.300ms="event" icon="bolt":placeholder="__('Event...')" class="w-full sm:w-48"/>
  </div>
 </div>

 <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
  <table class="w-full text-left text-sm">
   <thead>
    <tr class="border-b border-zinc-100 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-950/50">
     <th class="px-5 py-3 font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Action & Module') }}</th>
     <th class="px-5 py-3 font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Initiator') }}</th>
     <th class="px-5 py-3 font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Activity Details') }}</th>
     <th class="px-5 py-3 font-semibold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Timestamp') }}</th>
    </tr>
   </thead>
   <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
    @forelse ($logs as $log)
     <tr wire:key="activity-{{ $log->id }}" class="group transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
      <td class="whitespace-nowrap px-5 py-3.5">
       <div class="flex flex-col gap-1">
        <flux:badge size="sm" variant="solid" color="zinc" class="font-mono text-[10px] w-fit px-1.5 py-0">{{ strtoupper($log->event ?? 'activity') }}</flux:badge>
        <flux:text size="xs" variant="subtle" class="font-medium">{{ $log->log_name }}</flux:text>
       </div>
      </td>
      <td class="px-5 py-3.5">
       <div class="flex items-center gap-2">
        <flux:avatar :name="$log->causer?->name ?? 'System'" size="xs" class="ring-2 ring-white dark:ring-zinc-900"/>
        <span class="font-semibold text-zinc-700 dark:text-zinc-300 text-xs">{{ $log->causer?->name ?? __('System') }}</span>
       </div>
      </td>
      <td class="px-5 py-3.5">
       <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed max-w-md">
        {{ $log->description }}
        @if(isset($log->properties['attributes']))
         <span class="ml-1 text-[10px] text-zinc-400 group-hover:text-zinc-500 transition-colors">({{ count($log->properties['attributes']) }} {{ __('fields updated') }})</span>
        @endif
       </p>
      </td>
      <td class="whitespace-nowrap px-5 py-3.5">
       <flux:tooltip :content="$log->created_at->format('Y-m-d H:i:s')">
        <flux:text size="xs" variant="subtle" class="font-medium">{{ $log->created_at->diffForHumans() }}</flux:text>
       </flux:tooltip>
      </td>
     </tr>
    @empty
     <tr>
      <td colspan="4" class="px-5 py-20 text-center">
       <div class="flex flex-col items-center">
        <div class="flex size-14 items-center justify-center rounded-full bg-zinc-50 dark:bg-zinc-800 mb-3">
         <flux:icon icon="document-magnifying-glass" class="size-7 text-zinc-300"/>
        </div>
        <flux:heading size="sm" class="font-semibold">{{ __('No records identified') }}</flux:heading>
        <flux:text variant="subtle" class="mt-1 text-sm">{{ __('Adjust your filters or check back later for new activity.') }}</flux:text>
       </div>
      </td>
     </tr>
    @endforelse
   </tbody>
  </table>

  @if ($logs->hasPages())
   <div class="border-t border-zinc-100 p-6 bg-zinc-50/30 dark:border-zinc-800 dark:bg-zinc-950/10">
    {{ $logs->links() }}
   </div>
  @endif
 </div>
</div>



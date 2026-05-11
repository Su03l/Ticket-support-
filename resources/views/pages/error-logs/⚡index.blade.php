<?php

use App\Models\ErrorLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Error Diagnostics')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 #[Url(history: true)]
 public string $status = 'open';

 public function mount(): void
 {
  $this->authorize('viewAny', ErrorLog::class);
 }

 public function updatedStatus(): void
 {
  $this->resetPage();
 }

 public function with(): array
 {
  return [
   'errors' => ErrorLog::query()
    ->when($this->status === 'open', fn ($query) => $query->whereNull('resolved_at'))
    ->when($this->status === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
    ->latest()
    ->paginate(20),
  ];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div>
   <flux:heading size="xl"level="1">{{ __('Fault Management') }}</flux:heading>
   <flux:text variant="subtle" class="mt-1">{{ __('Isolated environment for identifying and triaging application runtime exceptions.') }}</flux:text>
  </div>

  <div class="flex items-center gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
   <flux:button size="sm" variant="ghost" wire:click="$set('status', 'open')" class="{{ $status === 'open' ? '!bg-white !shadow-sm dark:!bg-zinc-700' : '' }}">{{ __('Open') }}</flux:button>
   <flux:button size="sm" variant="ghost" wire:click="$set('status', 'resolved')" class="{{ $status === 'resolved' ? '!bg-white !shadow-sm dark:!bg-zinc-700' : '' }}">{{ __('Resolved') }}</flux:button>
   <flux:button size="sm" variant="ghost" wire:click="$set('status', 'all')" class="{{ $status === 'all' ? '!bg-white !shadow-sm dark:!bg-zinc-700' : '' }}">{{ __('All') }}</flux:button>
  </div>
 </div>

 <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
  <table class="w-full text-left text-sm">
   <thead>
    <tr class="border-b border-zinc-100 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-950/50">
     <th class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Error ID') }}</th>
     <th class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Exception & Diagnostic Message') }}</th>
     <th class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Status') }}</th>
     <th class="px-6 py-4 font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wide text-xs">{{ __('Occurrence') }}</th>
    </tr>
   </thead>
   <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
    @forelse ($errors as $error)
     <tr wire:key="error-{{ $error->id }}" class="group transition-all hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
      <td class="whitespace-nowrap px-6 py-4">
       <a href="{{ route('error-logs.show', $error) }}" wire:navigate class="inline-flex items-center font-mono text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
        #{{ $error->error_id }}
        <flux:icon icon="arrow-top-right-on-square" variant="mini" class="ml-1 size-3 opacity-0 group-hover:opacity-100 transition-opacity"/>
       </a>
      </td>
      <td class="px-6 py-4">
       <div class="flex flex-col gap-1 max-w-xl">
        <span class="font-bold text-zinc-900 dark:text-zinc-100 text-xs">{{ class_basename($error->exception_class) }}</span>
        <flux:text size="xs" variant="subtle" class="truncate">{{ $error->message }}</flux:text>
       </div>
      </td>
      <td class="px-6 py-4">
       @if ($error->resolved_at)
        <flux:badge size="sm" color="emerald" variant="subtle" class="font-bold uppercase tracking-widest text-[10px]">{{ __('Resolved') }}</flux:badge>
       @else
        <flux:badge size="sm" color="rose" variant="solid" class="font-bold uppercase tracking-widest text-[10px]">{{ __('Open') }}</flux:badge>
       @endif
      </td>
      <td class="whitespace-nowrap px-6 py-4">
       <flux:text size="xs" variant="subtle" class="font-medium">{{ $error->created_at->diffForHumans() }}</flux:text>
      </td>
     </tr>
    @empty
     <tr>
      <td colspan="4" class="px-6 py-24 text-center">
       <div class="flex flex-col items-center">
        <div class="flex size-16 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950/20 mb-4">
         <flux:icon icon="check-circle" class="size-8 text-emerald-500"/>
        </div>
        <flux:heading size="md">{{ __('System fully operational') }}</flux:heading>
        <flux:text variant="subtle" class="mt-1">{{ __('No exceptions have been logged within this scope.') }}</flux:text>
       </div>
      </td>
     </tr>
    @endforelse
   </tbody>
  </table>

  @if ($errors->hasPages())
   <div class="border-t border-zinc-100 p-6 bg-zinc-50/30 dark:border-zinc-800 dark:bg-zinc-950/10">
    {{ $errors->links() }}
   </div>
  @endif
 </div>
</div>


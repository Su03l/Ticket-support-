<div class="flex flex-col gap-4">
  @forelse($histories as $history)
    <div wire:key="history-{{ $history->id }}" class="relative flex gap-3">
      @if(!$loop->last)
        <div class="absolute inset-y-0 left-[0.625rem] top-8 w-0.5 bg-zinc-100 dark:bg-zinc-800" aria-hidden="true"></div>
      @endif

      <div class="relative z-10 flex size-5 shrink-0 items-center justify-center rounded-full bg-white ring-4 ring-white dark:bg-zinc-900 dark:ring-zinc-950">
        <div class="size-2 rounded-full {{ $loop->first ? 'bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]' : 'bg-zinc-300 dark:bg-zinc-700' }}"></div>
      </div>

      <div class="flex flex-1 flex-col gap-1.5 pb-6">
        <div class="flex flex-wrap items-center justify-between gap-x-2">
          <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="font-bold text-zinc-900 dark:text-white">{{ $history->changedBy->name ?? __('System') }}</span>
            <span class="text-zinc-500">{{ __('changed to') }}</span>
            <x-status-badge :status="$history->new_status->value ?? $history->new_status" class="!px-1.5 !py-0 text-[10px]" />
          </div>
          <span class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500">{{ $history->created_at->diffForHumans() }}</span>
        </div>

        @if($history->reason)
          <div class="rounded-lg bg-zinc-50 p-2.5 text-xs text-zinc-600 dark:bg-zinc-800/40 dark:text-zinc-400">
            <p class="leading-relaxed">{{ $history->reason }}</p>
          </div>
        @endif
      </div>
    </div>
  @empty
    <div class="flex flex-col items-center justify-center py-4 text-center">
      <flux:icon name="clock" class="size-6 text-zinc-200 dark:text-zinc-800" />
      <p class="mt-2 text-xs text-zinc-400">{{ __('No history yet.') }}</p>
    </div>
  @endforelse
</div>
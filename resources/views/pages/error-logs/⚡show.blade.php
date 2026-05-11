<?php

use App\Models\ErrorLog;
use App\Services\ErrorLogService;
use App\Enums\UserType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Fault Detail')] class extends Component
{
 use AuthorizesRequests;

 public ErrorLog $errorLog;
 public string $notes = '';

 public function mount(ErrorLog $errorLog): void
 {
  $this->authorize('view', $errorLog);
  $this->errorLog = $errorLog;
 }

 public function resolve(ErrorLogService $errors): void
 {
  $this->authorize('resolve', $this->errorLog);
  $this->errorLog = $errors->markResolved($this->errorLog, Auth::user(), $this->notes);
  
  flux()->toast(
   text: __('Incident #:id marked as resolved.', ['id' => $this->errorLog->error_id]),
   variant: 'success',
  );
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div>
   <div class="flex items-center gap-3">
    <flux:heading size="xl"level="1" class="font-mono">#{{ $errorLog->error_id }}</flux:heading>
    @if ($errorLog->resolved_at)
     <flux:badge color="emerald" variant="subtle" class="font-bold uppercase tracking-widest text-[10px]">{{ __('Resolved') }}</flux:badge>
    @else
     <flux:badge color="rose" variant="solid" class="font-bold uppercase tracking-widest text-[10px]">{{ __('Open') }}</flux:badge>
    @endif
   </div>
   <flux:text variant="subtle" class="mt-1 font-medium">{{ $errorLog->exception_class }}</flux:text>
  </div>

  <flux:button icon="chevron-left" variant="ghost":href="route('error-logs.index')" wire:navigate size="sm">{{ __('Back to index') }}</flux:button>
 </div>

 <div class="grid gap-8 lg:grid-cols-3">
  <div class="lg:col-span-2 flex flex-col gap-8">
   <section class="rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="lg" class="mb-6 flex items-center gap-2">
     <flux:icon icon="exclamation-circle" variant="mini" class="text-rose-500"/>
     {{ __('Diagnostic Message') }}
    </flux:heading>
    <div class="rounded-2xl bg-zinc-950 p-6 font-mono text-sm leading-relaxed text-zinc-100 shadow-inner">
     <p>{{ $errorLog->message }}</p>
    </div>
   </section>

   <section class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
    <div class="px-8 py-6 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
     <flux:heading size="lg" class="flex items-center gap-2">
      <flux:icon icon="code-bracket" variant="mini" class="text-blue-500"/>
      {{ __('Trace Analysis') }}
     </flux:heading>
     
     @if (Auth::user()->user_type === UserType::SuperAdmin)
      <flux:button icon="clipboard" variant="ghost" size="xs"x-on:click="navigator.clipboard.writeText($refs.trace.innerText); flux.toast({text: '{{ __('Trace copied to clipboard') }}', variant: 'success'})">{{ __('Copy Trace') }}</flux:button>
     @endif
    </div>
    
    <div class="p-0">
     @if (Auth::user()->user_type === UserType::SuperAdmin)
      <div class="relative group">
       <pre x-ref="trace" class="max-h-[600px] overflow-auto bg-zinc-950 p-8 font-mono text-[11px] leading-relaxed text-zinc-300 scrollbar-thin scrollbar-thumb-zinc-800">
<span class="text-zinc-500">// {{ $errorLog->file }}:{{ $errorLog->line }}</span>
{{ $errorLog->stack_trace }}</pre>
      </div>
     @else
      <div class="flex flex-col items-center justify-center py-24 bg-zinc-50/50 dark:bg-zinc-950/20">
       <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 mb-4">
        <flux:icon icon="lock-closed" class="size-8 text-zinc-400"/>
       </div>
       <flux:heading size="md">{{ __('Restricted Content') }}</flux:heading>
       <flux:text variant="subtle" class="mt-1 text-center max-w-sm">{{ __('For security governance, stack traces are limited to system architects and super administrators.') }}</flux:text>
      </div>
     @endif
    </div>
   </section>
  </div>

  <aside class="flex flex-col gap-8">
   <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="md" class="mb-5 uppercase tracking-wide text-zinc-400 text-xs font-bold">{{ __('Execution Context') }}</flux:heading>
    <div class="space-y-4">
     <div class="flex flex-col gap-1">
      <flux:text size="xs" variant="subtle" class="font-bold uppercase tracking-widest">{{ __('Detected At') }}</flux:text>
      <flux:text size="sm" class="font-semibold">{{ $errorLog->created_at->format('M d, Y • H:i:s') }}</flux:text>
     </div>
     <div class="flex flex-col gap-1">
      <flux:text size="xs" variant="subtle" class="font-bold uppercase tracking-widest">{{ __('Subject Identity') }}</flux:text>
      <flux:text size="sm" class="font-semibold">{{ $errorLog->user_id ? __('User ID: :id', ['id' => $errorLog->user_id]) : __('Anonymous Guest') }}</flux:text>
     </div>
     <div class="flex flex-col gap-1">
      <flux:text size="xs" variant="subtle" class="font-bold uppercase tracking-widest">{{ __('Resource URL') }}</flux:text>
      <flux:text size="sm" class="font-semibold break-all">{{ $errorLog->url ?? 'N/A' }}</flux:text>
     </div>
    </div>
   </section>

   <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="md" class="mb-5 uppercase tracking-wide text-zinc-400 text-xs font-bold">{{ __('Incident Resolution') }}</flux:heading>
    
    @if ($errorLog->resolved_at)
     <div class="space-y-6">
      <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30">
       <flux:icon icon="check-circle" class="size-6 text-emerald-600"/>
       <div>
        <flux:text size="xs" class="font-bold text-emerald-700 dark:text-emerald-400">{{ __('Resolved') }}</flux:text>
        <flux:text size="xs" variant="subtle">{{ $errorLog->resolved_at->diffForHumans() }}</flux:text>
       </div>
      </div>
      
      <div class="flex flex-col gap-2">
       <flux:text size="xs" variant="subtle" class="font-bold uppercase tracking-widest">{{ __('By Agent') }}</flux:text>
       <div class="flex items-center gap-2">
        <flux:avatar :name="$errorLog->resolvedBy?->name ?? 'System'" size="xs"/>
        <flux:text size="sm" class="font-semibold">{{ $errorLog->resolvedBy?->name ?? __('System') }}</flux:text>
       </div>
      </div>

      @if ($errorLog->resolution_notes)
       <div class="flex flex-col gap-2">
        <flux:text size="xs" variant="subtle" class="font-bold uppercase tracking-widest">{{ __('Resolution Log') }}</flux:text>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed italic border-l-2 border-zinc-200 pl-3 dark:border-zinc-700">{{ $errorLog->resolution_notes }}</p>
       </div>
      @endif
     </div>
    @else
     <form wire:submit="resolve" class="space-y-4">
      <flux:field>
       <flux:label>{{ __('Closure Notes') }}</flux:label>
       <flux:textarea wire:model="notes"rows="4"placeholder="{{ __('Document the fix or mitigation...') }}"/>
      </flux:field>
      <flux:button type="submit" variant="primary" icon="check" class="w-full">{{ __('Execute Closure') }}</flux:button>
     </form>
    @endif
   </section>
  </aside>
 </div>
</div>



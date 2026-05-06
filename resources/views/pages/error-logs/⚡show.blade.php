<?php

use App\Models\ErrorLog;
use App\Services\ErrorLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Error detail')] class extends Component
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
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $errorLog->error_id }}</flux:heading>
        <flux:text>{{ $errorLog->exception_class }}</flux:text>
    </div>
    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:text>{{ $errorLog->message }}</flux:text>
        <pre class="mt-4 overflow-auto rounded bg-zinc-950 p-4 text-xs text-zinc-100">{{ $errorLog->file }}:{{ $errorLog->line }}
{{ $errorLog->stack_trace }}</pre>
    </div>
    <div class="flex flex-col gap-3">
        <flux:textarea wire:model="notes" :label="__('Resolution notes')" rows="3" />
        <flux:button wire:click="resolve" variant="primary" class="self-start">{{ __('Mark resolved') }}</flux:button>
    </div>
</div>

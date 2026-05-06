<?php

use App\Models\ErrorLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Error logs')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $status = 'open';

    public function mount(): void
    {
        $this->authorize('viewAny', ErrorLog::class);
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

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Error logs') }}</flux:heading>
            <flux:text>{{ __('Secure diagnostics for super admins only.') }}</flux:text>
        </div>
        <flux:select wire:model.live="status" class="w-40">
            <flux:select.option value="open">{{ __('Open') }}</flux:select.option>
            <flux:select.option value="resolved">{{ __('Resolved') }}</flux:select.option>
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
        </flux:select>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @forelse ($errors as $error)
            <a wire:key="error-{{ $error->id }}" href="{{ route('error-logs.show', $error) }}" wire:navigate class="block border-b border-zinc-200 p-4 last:border-b-0 dark:border-zinc-800">
                <flux:heading size="sm">{{ $error->error_id }}</flux:heading>
                <flux:text>{{ $error->exception_class }} · {{ $error->message }}</flux:text>
            </a>
        @empty
            <div class="p-10 text-center"><flux:text>{{ __('No error logs found.') }}</flux:text></div>
        @endforelse
    </div>
    {{ $errors->links() }}
</div>

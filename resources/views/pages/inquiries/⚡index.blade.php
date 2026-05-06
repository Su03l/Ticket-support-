<?php

use App\Enums\InquiryStatus;
use App\Models\Department;
use App\Models\Inquiry;
use App\Services\InquiryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Inquiries')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $status = '';
    public string $departmentId = '';
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Inquiry::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'departmentId', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function with(InquiryService $inquiries): array
    {
        $user = Auth::user();

        return [
            'inquiries' => $inquiries->listInquiriesForUser($user, [
                'status' => $this->status ?: null,
                'department_id' => $this->departmentId ?: null,
                'search' => $this->search ?: null,
            ]),
            'statuses' => InquiryStatus::cases(),
            'departments' => Department::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Inquiries') }}</flux:heading>
            <flux:text>{{ __('Answer lightweight requests or convert them into tickets.') }}</flux:text>
        </div>

        @can('create', \App\Models\Inquiry::class)
            <flux:button variant="primary" icon="plus" :href="route('inquiries.create')" wire:navigate>{{ __('New inquiry') }}</flux:button>
        @endcan
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number or subject')" />
        <flux:select wire:model.live="status">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $inquiryStatus)
                <flux:select.option value="{{ $inquiryStatus->value }}">{{ __(str_replace('_', ' ', $inquiryStatus->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="departmentId">
            <flux:select.option value="">{{ __('All departments') }}</flux:select.option>
            @foreach ($departments as $department)
                <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @forelse ($inquiries as $inquiry)
            <a wire:key="inquiry-{{ $inquiry->id }}" href="{{ route('inquiries.show', $inquiry) }}" wire:navigate class="block border-b border-zinc-200 p-4 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="sm">{{ $inquiry->inquiry_number }}</flux:heading>
                            <flux:badge color="blue" size="sm">{{ __(str_replace('_', ' ', $inquiry->status->value)) }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 truncate">{{ $inquiry->subject }}</flux:text>
                    </div>
                    <div class="grid shrink-0 gap-1 text-sm text-zinc-500 sm:grid-cols-3 sm:gap-6">
                        <span>{{ $inquiry->department?->name ?? __('General') }}</span>
                        <span>{{ $inquiry->assignedAgent?->name ?? __('Unassigned') }}</span>
                        <span>{{ $inquiry->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="p-10 text-center">
                <flux:heading size="md">{{ __('No inquiries found.') }}</flux:heading>
                <flux:text>{{ __('Inquiries matching your filters will appear here.') }}</flux:text>
            </div>
        @endforelse
    </div>

    {{ $inquiries->links() }}
</div>

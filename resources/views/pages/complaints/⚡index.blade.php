<?php

use App\Enums\ComplaintSeverity;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Services\ComplaintService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Complaints')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $status = '';

    public string $severity = '';

    public string $departmentId = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Complaint::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'severity', 'departmentId', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function with(ComplaintService $complaints): array
    {
        $user = Auth::user();

        return [
            'complaints' => $complaints->listComplaintsForUser($user, [
                'status' => $this->status ?: null,
                'severity' => $this->severity ?: null,
                'department_id' => $this->departmentId ?: null,
                'search' => $this->search ?: null,
            ]),
            'statuses' => ComplaintStatus::cases(),
            'severities' => ComplaintSeverity::cases(),
            'departments' => Department::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Complaints') }}</flux:heading>
            <flux:text>{{ __('Handle sensitive customer concerns with clear ownership and audit history.') }}</flux:text>
        </div>

        @can('create', \App\Models\Complaint::class)
            <flux:button variant="primary" icon="plus" :href="route('complaints.create')" wire:navigate>
                {{ __('New complaint') }}
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-3 md:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number or title')" />
        <flux:select wire:model.live="status">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $complaintStatus)
                <flux:select.option value="{{ $complaintStatus->value }}">{{ __(str_replace('_', ' ', $complaintStatus->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="severity">
            <flux:select.option value="">{{ __('All severities') }}</flux:select.option>
            @foreach ($severities as $complaintSeverity)
                <flux:select.option value="{{ $complaintSeverity->value }}">{{ __($complaintSeverity->value) }}</flux:select.option>
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
        @forelse ($complaints as $complaint)
            <a wire:key="complaint-{{ $complaint->id }}" href="{{ route('complaints.show', $complaint) }}" wire:navigate class="block border-b border-zinc-200 p-4 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="sm">{{ $complaint->complaint_number }}</flux:heading>
                            <flux:badge color="blue" size="sm">{{ __(str_replace('_', ' ', $complaint->status->value)) }}</flux:badge>
                            <flux:badge color="{{ $complaint->severity === App\Enums\ComplaintSeverity::Critical ? 'red' : 'zinc' }}" size="sm">{{ __($complaint->severity->value) }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 truncate">{{ $complaint->title }}</flux:text>
                    </div>

                    <div class="grid shrink-0 gap-1 text-sm text-zinc-500 sm:grid-cols-3 sm:gap-6">
                        <span>{{ $complaint->department?->name ?? __('General') }}</span>
                        <span>{{ $complaint->assignedAgent?->name ?? __('Unassigned') }}</span>
                        <span>{{ $complaint->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="p-10 text-center">
                <flux:heading size="md">{{ __('No complaints found.') }}</flux:heading>
                <flux:text>{{ __('Complaints matching your filters will appear here.') }}</flux:text>
            </div>
        @endforelse
    </div>

    {{ $complaints->links() }}
</div>

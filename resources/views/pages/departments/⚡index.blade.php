<?php

use App\Enums\DepartmentStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Departments')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $companyId = '';

    public string $name = '';

    public string $description = '';

    public string $managerId = '';

    public string $deputyId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Department::class);
        $this->companyId = (string) (Auth::user()->company_id ?? Company::query()->orderBy('name')->value('id') ?? '');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'companyId'], true)) {
            $this->resetPage();
        }
    }

    public function createDepartment(DepartmentService $departments): void
    {
        $this->authorize('create', Department::class);

        $companyId = Auth::user()->company_id ?? (int) $this->companyId;

        $validated = $this->validate([
            'companyId' => [Auth::user()->company_id === null ? 'required' : 'nullable', 'nullable', Rule::exists('companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'managerId' => ['nullable', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'deputyId' => ['nullable', Rule::exists('users', 'id')->where('company_id', $companyId)],
        ]);

        $company = Company::query()->findOrFail($companyId);
        $departments->createDepartment($company, [
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'manager_id' => $validated['managerId'] ?: null,
            'deputy_id' => $validated['deputyId'] ?: null,
        ]);

        $this->reset(['name', 'description', 'managerId', 'deputyId']);
        Flux::toast(variant: 'success', text: __('Department created.'));
    }

    public function toggleStatus(Department $department, DepartmentService $departments): void
    {
        $this->authorize('update', $department);

        $department->status === DepartmentStatus::Active
            ? $departments->deactivate($department)
            : $departments->activate($department);

        Flux::toast(variant: 'success', text: __('Department status updated.'));
    }

    public function with(): array
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? ($this->companyId ?: null);

        return [
            'departments' => Department::query()
                ->with(['company:id,name', 'manager:id,name', 'deputy:id,name'])
                ->withCount(['members', 'tickets'])
                ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
                ->when($user->company_id === null && $companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'staff' => User::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->whereNotNull('company_id')
                ->whereNotIn('user_type', ['customer'])
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Departments') }}</flux:heading>
        <flux:text>{{ __('Organize support teams, managers, deputies, and workload.') }}</flux:text>
    </div>

    @can('create', \App\Models\Department::class)
        <form wire:submit="createDepartment" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-3 lg:grid-cols-5">
                @if (auth()->user()->company_id === null)
                    <flux:select wire:model.live="companyId" :label="__('Company')">
                        @foreach ($companies as $company)
                            <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
                <flux:input wire:model="name" :label="__('Department name')" />
                <flux:select wire:model="managerId" :label="__('Manager')">
                    <flux:select.option value="">{{ __('No manager') }}</flux:select.option>
                    @foreach ($staff as $member)
                        <flux:select.option value="{{ $member->id }}">{{ $member->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="deputyId" :label="__('Deputy')">
                    <flux:select.option value="">{{ __('No deputy') }}</flux:select.option>
                    @foreach ($staff as $member)
                        <flux:select.option value="{{ $member->id }}">{{ $member->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="description" :label="__('Description')" />
            </div>
            <div class="mt-4 flex justify-end">
                <flux:button type="submit" variant="primary" icon="plus">{{ __('Create department') }}</flux:button>
            </div>
        </form>
    @endcan

    <div class="grid gap-3 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search departments')" />
        @if (auth()->user()->company_id === null)
            <flux:select wire:model.live="companyId">
                @foreach ($companies as $company)
                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($departments as $department)
            <div wire:key="department-{{ $department->id }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="sm">{{ $department->name }}</flux:heading>
                        <flux:text>{{ $department->company->name }}</flux:text>
                    </div>
                    <flux:badge size="sm">{{ __(ucfirst($department->status->value)) }}</flux:badge>
                </div>
                <div class="mt-4 grid gap-2 text-sm">
                    <div class="flex justify-between gap-3"><span>{{ __('Manager') }}</span><strong>{{ $department->manager?->name ?? __('Unassigned') }}</strong></div>
                    <div class="flex justify-between gap-3"><span>{{ __('Deputy') }}</span><strong>{{ $department->deputy?->name ?? __('Unassigned') }}</strong></div>
                    <div class="flex justify-between gap-3"><span>{{ __('Members') }}</span><strong>{{ $department->members_count }}</strong></div>
                    <div class="flex justify-between gap-3"><span>{{ __('Tickets') }}</span><strong>{{ $department->tickets_count }}</strong></div>
                </div>
                @can('update', $department)
                    <flux:button class="mt-4" size="sm" variant="ghost" wire:click="toggleStatus({{ $department->id }})">
                        {{ $department->status === \App\Enums\DepartmentStatus::Active ? __('Deactivate') : __('Activate') }}
                    </flux:button>
                @endcan
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                <flux:heading size="md">{{ __('No departments found.') }}</flux:heading>
            </div>
        @endforelse
    </div>

    {{ $departments->links() }}
</div>

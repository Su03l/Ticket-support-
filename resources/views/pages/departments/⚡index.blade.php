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
                ->paginate(12),
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

<div x-data="{ showForm: false }" class="flex flex-col gap-10">
    <x-page-header :title="__('Departments')" :description="__('Organize support teams, managers, deputies, and workload.')">
        <x-slot:actions>
            @can('create', \App\Models\Department::class)
                <flux:button x-on:click="showForm = !showForm" variant="primary" icon="plus" class="font-bold rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Create department') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @can('create', \App\Models\Department::class)
        <div x-show="showForm" x-collapse x-cloak>
            <x-section-card :heading="__('New department')" icon="squares-2x2">
                <x-slot:headerAction>
                    <button type="button" x-on:click="showForm = false" class="rounded-lg p-1 text-zinc-400 transition-all hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                </x-slot:headerAction>

                <form wire:submit="createDepartment">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="grid gap-6">
                            @if (auth()->user()->company_id === null)
                                <flux:select wire:model.live="companyId" :label="__('Company')" required>
                                    @foreach ($companies as $company)
                                        <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif
                            <flux:input wire:model="name" :label="__('Department name')" placeholder="e.g. Technical Support" required />
                            <flux:input wire:model="description" :label="__('Description')" placeholder="Briefly describe the department role" />
                        </div>
                        <div class="grid gap-6">
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
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-end gap-4 border-t border-zinc-100/80 pt-6 dark:border-zinc-800">
                        <flux:button type="button" x-on:click="showForm = false" variant="ghost" class="font-bold rounded-xl">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="font-bold rounded-xl px-8 shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Save department') }}</flux:button>
                    </div>
                </form>
            </x-section-card>
        </div>
    @endcan

    <x-filter-bar columns="sm:grid-cols-2 lg:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search departments...')" class="lg:col-span-2" />
        @if (auth()->user()->company_id === null)
            <flux:select wire:model.live="companyId">
                @foreach ($companies as $company)
                    <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </x-filter-bar>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @forelse ($departments as $department)
            <div wire:key="department-{{ $department->id }}" class="group flex flex-col overflow-hidden rounded-3xl border border-zinc-200/60 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-200/40 dark:border-zinc-800/60 dark:bg-zinc-900 dark:hover:shadow-none">
                <div class="flex items-start justify-between p-8 pb-6">
                    <div class="min-w-0">
                        <flux:heading size="md" class="truncate font-bold tracking-tight text-zinc-900 dark:text-white">{{ $department->name }}</flux:heading>
                        <flux:text class="mt-1 text-sm font-bold text-blue-600 dark:text-blue-400">{{ $department->company->name }}</flux:text>
                    </div>
                    <x-status-badge :status="$department->status->value" class="font-bold px-2.5" />
                </div>

                <div class="flex flex-1 flex-col px-8 pb-8 pt-2">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">{{ __('Manager') }}</span>
                            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ $department->manager?->name ?? __('Unassigned') }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">{{ __('Deputy') }}</span>
                            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ $department->deputy?->name ?? __('Unassigned') }}</span>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 divide-x divide-zinc-100 rounded-2xl border border-zinc-100 bg-zinc-50/50 rtl:divide-x-reverse dark:divide-zinc-800 dark:border-zinc-800/60 dark:bg-zinc-800/40">
                        <div class="flex flex-col items-center py-4 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-zinc-400">{{ __('Members') }}</span>
                            <span class="mt-1 text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ $department->members_count }}</span>
                        </div>
                        <div class="flex flex-col items-center py-4 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-zinc-400">{{ __('Active Tickets') }}</span>
                            <span class="mt-1 text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ $department->tickets_count }}</span>
                        </div>
                    </div>
                </div>

                @can('update', $department)
                    <div class="border-t border-zinc-100/80 bg-zinc-50/30 p-4 dark:border-zinc-800/60 dark:bg-zinc-900/50">
                        <flux:button size="sm" variant="ghost" wire:click="toggleStatus({{ $department->id }})" class="w-full font-bold rounded-xl hover:bg-white dark:hover:bg-zinc-800 transition-all" wire:loading.attr="disabled">
                            {{ $department->status === \App\Enums\DepartmentStatus::Active ? __('Deactivate department') : __('Activate department') }}
                        </flux:button>
                    </div>
                @endcan
            </div>
        @empty
            <x-empty-state icon="squares-2x2" :heading="__('No departments found.')" :description="__('Create your first department to start organizing teams and support workflows.')">
                <x-slot:action>
                    @can('create', \App\Models\Department::class)
                        <flux:button x-on:click="showForm = true" variant="primary" size="sm" icon="plus" class="font-bold rounded-xl px-6">{{ __('Create department') }}</flux:button>
                    @endcan
                </x-slot:action>
            </x-empty-state>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $departments->links() }}
    </div>
</div>
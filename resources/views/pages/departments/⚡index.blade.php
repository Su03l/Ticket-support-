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

<div x-data="{ showForm: false }" class="flex flex-col gap-6">
 <x-page-header :title="__('Departments')":description="__('Organize support teams, managers, deputies, and workload.')">
  <x-slot:actions>
   @can('create', \App\Models\Department::class)
    <flux:button x-on:click="showForm = !showForm" variant="primary" icon="plus">{{ __('Create department') }}</flux:button>
   @endcan
  </x-slot:actions>
 </x-page-header>

 @can('create', \App\Models\Department::class)
  <div x-show="showForm"x-collapse x-cloak>
   <x-section-card :heading="__('New department')" icon="squares-2x2">
    <x-slot:headerAction>
     <button type="button"x-on:click="showForm = false" class="flex size-8 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
      <flux:icon.x-mark class="size-4"/>
     </button>
    </x-slot:headerAction>

    <form wire:submit="createDepartment">
     <div class="grid gap-4 sm:grid-cols-2">
      <div class="flex flex-col gap-4">
       @if (auth()->user()->company_id === null)
        <flux:select wire:model.live="companyId":label="__('Company')"required>
         @foreach ($companies as $company)
          <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
         @endforeach
        </flux:select>
       @endif
       <flux:input wire:model="name":label="__('Department name')"placeholder="e.g. Technical Support"required />
       <flux:input wire:model="description":label="__('Description')"placeholder="Briefly describe the department role"/>
      </div>
      <div class="flex flex-col gap-4">
       <flux:select wire:model="managerId":label="__('Manager')">
        <flux:select.option value="">{{ __('No manager') }}</flux:select.option>
        @foreach ($staff as $member)
         <flux:select.option value="{{ $member->id }}">{{ $member->name }}</flux:select.option>
        @endforeach
       </flux:select>
       <flux:select wire:model="deputyId":label="__('Deputy')">
        <flux:select.option value="">{{ __('No deputy') }}</flux:select.option>
        @foreach ($staff as $member)
         <flux:select.option value="{{ $member->id }}">{{ $member->name }}</flux:select.option>
        @endforeach
       </flux:select>
      </div>
     </div>
     <div class="mt-5 flex items-center justify-end gap-3 border-t border-zinc-100/80 pt-4 dark:border-zinc-800">
      <flux:button type="button"x-on:click="showForm = false" variant="ghost">{{ __('Cancel') }}</flux:button>
      <flux:button type="submit" variant="primary">{{ __('Save department') }}</flux:button>
     </div>
    </form>
   </x-section-card>
  </div>
 @endcan

 <x-filter-bar columns="sm:grid-cols-2 lg:grid-cols-3">
  <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass":placeholder="__('Search departments...')" class="lg:col-span-2"/>
  @if (auth()->user()->company_id === null)
   <flux:select wire:model.live="companyId">
    @foreach ($companies as $company)
     <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
    @endforeach
   </flux:select>
  @endif
 </x-filter-bar>

 <div class="card overflow-hidden">
  {{-- Table header --}}
  <div class="hidden border-b border-zinc-100/80 bg-zinc-50/80 px-6 py-3 dark:border-zinc-800/80 dark:bg-zinc-800/30 lg:grid lg:grid-cols-[1.5fr_1fr_1fr_8rem_10rem] lg:items-center lg:gap-4">
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Department') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Leadership') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Stats') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Status') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 text-right">{{ __('Actions') }}</span>
  </div>

  @forelse ($departments as $department)
   <x-list-row wire:key="department-{{ $department->id }}" class="group px-6 lg:grid-cols-[1.5fr_1fr_1fr_8rem_10rem] lg:items-center lg:gap-4">
    <div class="flex items-center gap-4">
     <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-sm font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
      {{ mb_substr($department->name, 0, 1) }}
     </div>
     <div class="min-w-0">
      <p class="text-sm font-semibold text-zinc-800 group-hover:text-zinc-900 dark:text-zinc-200 dark:group-hover:text-white">{{ $department->name }}</p>
      <p class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ $department->company->name }}</p>
     </div>
    </div>

    <div class="flex flex-col gap-1 text-xs text-zinc-500 dark:text-zinc-400">
     <div class="flex items-center gap-2">
      <span class="font-bold text-zinc-400">{{ __('M:') }}</span>
      <span class="truncate">{{ $department->manager?->name ?? __('Unassigned') }}</span>
     </div>
     <div class="flex items-center gap-2">
      <span class="font-bold text-zinc-400">{{ __('D:') }}</span>
      <span class="truncate">{{ $department->deputy?->name ?? __('Unassigned') }}</span>
     </div>
    </div>

    <div class="flex items-center gap-4 text-xs font-bold">
     <div class="flex flex-col items-center">
      <span class="text-[10px] uppercase text-zinc-400">{{ __('Members') }}</span>
      <span class="text-zinc-700 dark:text-zinc-300">{{ $department->members_count }}</span>
     </div>
     <div class="flex flex-col items-center">
      <span class="text-[10px] uppercase text-zinc-400">{{ __('Tickets') }}</span>
      <span class="text-zinc-700 dark:text-zinc-300">{{ $department->tickets_count }}</span>
     </div>
    </div>

    <x-status-badge :status="$department->status->value"/>

    <div class="flex justify-end gap-2">
     @can('update', $department)
      <flux:button size="sm" variant="ghost" wire:click="toggleStatus({{ $department->id }})" wire:loading.attr="disabled">
       {{ $department->status === \App\Enums\DepartmentStatus::Active ? __('Deactivate') : __('Activate') }}
      </flux:button>
     @endcan
    </div>
   </x-list-row>
  @empty
   <x-empty-state icon="squares-2x2":heading="__('No departments found.')":description="__('Create your first department to start organizing teams and support workflows.')" class="py-14">
    <x-slot:action>
     @can('create', \App\Models\Department::class)
      <flux:button x-on:click="showForm = true" variant="primary" size="sm" icon="plus">{{ __('Create department') }}</flux:button>
     @endcan
    </x-slot:action>
   </x-empty-state>
  @endforelse
 </div>

 <div class="mt-2">
  {{ $departments->links() }}
 </div>
</div>
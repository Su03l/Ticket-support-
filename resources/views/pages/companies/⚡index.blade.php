<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Services\CompanyService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Companies')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 public string $search = '';

 public string $status = '';

 public string $name = '';

 public string $email = '';

 public string $phone = '';

 public string $website = '';

 public string $planId = '';

 public function mount(): void
 {
  $this->authorize('viewAny', Company::class);
 }

 public function updated(string $property): void
 {
  if (in_array($property, ['search', 'status'], true)) {
   $this->resetPage();
  }
 }

 public function createCompany(CompanyService $companies): void
 {
  $this->authorize('create', Company::class);

  $validated = $this->validate([
   'name' => ['required', 'string', 'max:255'],
   'email' => ['nullable', 'email', 'max:255'],
   'phone' => ['nullable', 'string', 'max:50'],
   'website' => ['nullable', 'url', 'max:255'],
   'planId' => ['nullable', Rule::exists('plans', 'id')],
  ]);

  $companies->createCompany([
   'name' => $validated['name'],
   'email' => $validated['email'] ?: null,
   'phone' => $validated['phone'] ?: null,
   'website' => $validated['website'] ?: null,
   'plan_id' => $validated['planId'] ?: null,
  ]);

  $this->reset(['name', 'email', 'phone', 'website', 'planId']);
  Flux::toast(variant: 'success', text: __('Company created.'));
 }

 public function toggleStatus(Company $company, CompanyService $companies): void
 {
  $this->authorize('update', $company);

  $company->status === CompanyStatus::Active
   ? $companies->suspend($company)
   : $companies->activate($company);

  Flux::toast(variant: 'success', text: __('Company status updated.'));
 }

 public function with(): array
 {
  return [
   'companies' => Company::query()
    ->withCount(['users', 'departments', 'tickets'])
    ->with('plan:id,name')
    ->when(Auth::user()->company_id !== null, fn ($query) => $query->whereKey(Auth::user()->company_id))
    ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
    ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
    ->latest()
    ->paginate(12),
   'statuses' => CompanyStatus::cases(),
   'plans' => Plan::query()->where('is_active', true)->orderBy('price')->get(['id', 'name']),
  ];
 }
}; ?>

<div x-data="{ showForm: false }" class="flex flex-col gap-6">
 <x-page-header :title="__('Companies')":description="__('Manage tenant companies, subscriptions, and workspace capacity.')">
  <x-slot:actions>
   @can('create', \App\Models\Company::class)
    <flux:button x-on:click="showForm = !showForm" variant="primary" icon="plus">{{ __('Create company') }}</flux:button>
   @endcan
  </x-slot:actions>
 </x-page-header>

 @can('create', \App\Models\Company::class)
  <div x-show="showForm"x-collapse x-cloak>
   <x-section-card :heading="__('Create company')" icon="building-office-2">
    <x-slot:headerAction>
     <button type="button"x-on:click="showForm = false" class="flex size-8 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
      <flux:icon.x-mark class="size-4"/>
     </button>
    </x-slot:headerAction>

    <form wire:submit="createCompany">
     <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <flux:input wire:model="name":label="__('Company name')"placeholder="Acme Corp"required />
      <flux:input wire:model="email":label="__('Email')" type="email"placeholder="admin@acme.com"/>
      <flux:input wire:model="phone":label="__('Phone')"placeholder="+1 (555) 000-0000"/>
      <flux:input wire:model="website":label="__('Website')" type="url"placeholder="https://acme.com"/>
      <div class="sm:col-span-2">
       <flux:select wire:model="planId":label="__('Plan')">
        <flux:select.option value="">{{ __('No plan') }}</flux:select.option>
        @foreach ($plans as $plan)
         <flux:select.option value="{{ $plan->id }}">{{ $plan->name }}</flux:select.option>
        @endforeach
       </flux:select>
      </div>
     </div>
     <div class="mt-5 flex items-center justify-end gap-3 border-t border-zinc-100/80 pt-4 dark:border-zinc-800">
      <flux:button type="button"x-on:click="showForm = false" variant="ghost">{{ __('Cancel') }}</flux:button>
      <flux:button type="submit" variant="primary">{{ __('Save company') }}</flux:button>
     </div>
    </form>
   </x-section-card>
  </div>
 @endcan

 <x-filter-bar columns="sm:grid-cols-2 lg:grid-cols-3">
  <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass":placeholder="__('Search companies...')" class="lg:col-span-2"/>
  <flux:select wire:model.live="status">
   <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
   @foreach ($statuses as $statusOption)
    <flux:select.option value="{{ $statusOption->value }}">{{ __(ucfirst($statusOption->value)) }}</flux:select.option>
   @endforeach
  </flux:select>
 </x-filter-bar>

 <div class="card overflow-hidden">
  {{-- Table header --}}
  <div class="hidden border-b border-zinc-100/80 bg-zinc-50/80 px-6 py-3 dark:border-zinc-800/80 dark:bg-zinc-800/30 lg:grid lg:grid-cols-[1.5fr_1fr_1fr_8rem_10rem] lg:items-center lg:gap-4">
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Company') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Contact') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Stats') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Status') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 text-right">{{ __('Actions') }}</span>
  </div>

  @forelse ($companies as $company)
   <x-list-row wire:key="company-{{ $company->id }}" class="group px-6 lg:grid-cols-[1.5fr_1fr_1fr_8rem_10rem] lg:items-center lg:gap-4">
    <div class="flex items-center gap-4">
     <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-sm font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
      {{ mb_substr($company->name, 0, 1) }}
     </div>
     <div class="min-w-0">
      <p class="text-sm font-semibold text-zinc-800 group-hover:text-zinc-900 dark:text-zinc-200 dark:group-hover:text-white">{{ $company->name }}</p>
      <p class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ $company->plan?->name ?? __('No plan') }}</p>
     </div>
    </div>

    <div class="flex flex-col gap-1 text-xs text-zinc-500 dark:text-zinc-400">
     <div class="flex items-center gap-2">
      <flux:icon.envelope class="size-3 shrink-0 opacity-70"/>
      <span class="truncate">{{ $company->email ?? __('No email') }}</span>
     </div>
     <div class="flex items-center gap-2">
      <flux:icon.phone class="size-3 shrink-0 opacity-70"/>
      <span>{{ $company->phone ?? __('No phone') }}</span>
     </div>
    </div>

    <div class="flex items-center gap-4 text-xs font-bold">
     <div class="flex flex-col items-center">
      <span class="text-[10px] uppercase text-zinc-400">{{ __('Users') }}</span>
      <span class="text-zinc-700 dark:text-zinc-300">{{ $company->users_count }}</span>
     </div>
     <div class="flex flex-col items-center">
      <span class="text-[10px] uppercase text-zinc-400">{{ __('Depts') }}</span>
      <span class="text-zinc-700 dark:text-zinc-300">{{ $company->departments_count }}</span>
     </div>
     <div class="flex flex-col items-center">
      <span class="text-[10px] uppercase text-zinc-400">{{ __('Tickets') }}</span>
      <span class="text-zinc-700 dark:text-zinc-300">{{ $company->tickets_count }}</span>
     </div>
    </div>

    <x-status-badge :status="$company->status->value"/>

    <div class="flex justify-end gap-2">
     @can('update', $company)
      <flux:button size="sm" variant="ghost" wire:click="toggleStatus({{ $company->id }})" wire:loading.attr="disabled">
       {{ $company->status === \App\Enums\CompanyStatus::Active ? __('Suspend') : __('Activate') }}
      </flux:button>
     @endcan
    </div>
   </x-list-row>
  @empty
   <x-empty-state icon="building-office-2":heading="__('No companies found.')":description="__('Get started by creating a new company or adjusting your search filters.')" class="py-14">
    <x-slot:action>
     @can('create', \App\Models\Company::class)
      <flux:button x-on:click="showForm = true" variant="primary" size="sm" icon="plus">{{ __('Create company') }}</flux:button>
     @endcan
    </x-slot:action>
   </x-empty-state>
  @endforelse
 </div>

 <div class="mt-2">
  {{ $companies->links() }}
 </div>
</div>
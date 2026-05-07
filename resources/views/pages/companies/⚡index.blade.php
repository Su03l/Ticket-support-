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

<div x-data="{ showForm: false }" class="flex flex-col gap-10">
    <x-page-header :title="__('Companies')" :description="__('Manage tenant companies, subscriptions, and workspace capacity.')">
        <x-slot:actions>
            @can('create', \App\Models\Company::class)
                <flux:button x-on:click="showForm = !showForm" variant="primary" icon="plus" class="font-bold rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Create company') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @can('create', \App\Models\Company::class)
        <div x-show="showForm" x-collapse x-cloak>
            <x-section-card :heading="__('Create company')" icon="building-office-2">
                <x-slot:headerAction>
                    <button type="button" x-on:click="showForm = false" class="rounded-lg p-1 text-zinc-400 transition-all hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                </x-slot:headerAction>

                <form wire:submit="createCompany">
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <flux:input wire:model="name" :label="__('Company name')" placeholder="Acme Corp" />
                        <flux:input wire:model="email" :label="__('Email')" type="email" placeholder="admin@acme.com" />
                        <flux:input wire:model="phone" :label="__('Phone')" placeholder="+1 (555) 000-0000" />
                        <flux:input wire:model="website" :label="__('Website')" type="url" placeholder="https://acme.com" />
                        <div class="md:col-span-2 lg:col-span-2">
                            <flux:select wire:model="planId" :label="__('Plan')">
                                <flux:select.option value="">{{ __('No plan') }}</flux:select.option>
                                @foreach ($plans as $plan)
                                    <flux:select.option value="{{ $plan->id }}">{{ $plan->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                    <div class="mt-8 flex items-center justify-end gap-4 border-t border-zinc-100/80 pt-6 dark:border-zinc-800">
                        <flux:button type="button" x-on:click="showForm = false" variant="ghost" class="font-bold rounded-xl">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="font-bold rounded-xl px-8 shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Save company') }}</flux:button>
                    </div>
                </form>
            </x-section-card>
        </div>
    @endcan

    <x-filter-bar columns="sm:grid-cols-2 lg:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search companies...')" class="lg:col-span-2" />
        <flux:select wire:model.live="status">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $statusOption)
                <flux:select.option value="{{ $statusOption->value }}">{{ __(ucfirst($statusOption->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
    </x-filter-bar>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($companies as $company)
            <div wire:key="company-{{ $company->id }}" class="group flex flex-col overflow-hidden rounded-3xl border border-zinc-200/60 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-200/40 dark:border-zinc-800/60 dark:bg-zinc-900 dark:hover:shadow-none">
                <div class="flex items-start justify-between p-8 pb-6">
                    <div class="flex items-center gap-4">
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-zinc-100/80 text-xl font-bold text-zinc-600 ring-1 ring-zinc-200/50 transition-all group-hover:bg-zinc-900 group-hover:text-white dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700/50 dark:group-hover:bg-white dark:group-hover:text-zinc-950">
                            {{ mb_substr($company->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <flux:heading size="md" class="truncate font-bold tracking-tight text-zinc-900 dark:text-white">{{ $company->name }}</flux:heading>
                            <flux:text class="mt-1 text-sm font-bold text-blue-600 dark:text-blue-400">{{ $company->plan?->name ?? __('No plan') }}</flux:text>
                        </div>
                    </div>
                    <x-status-badge :status="$company->status->value" class="font-bold px-2.5" />
                </div>

                <div class="flex flex-1 flex-col px-8 pb-8 pt-2">
                    <div class="flex flex-col gap-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        <div class="flex items-center gap-3">
                            <flux:icon.envelope class="size-4 shrink-0 opacity-70" />
                            <span class="truncate">{{ $company->email ?? __('No email') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:icon.phone class="size-4 shrink-0 opacity-70" />
                            <span class="truncate font-mono">{{ $company->phone ?? __('Unknown') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:icon.globe-alt class="size-4 shrink-0 opacity-70" />
                            <span class="truncate">{{ $company->website ?? __('No website') }}</span>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-3 divide-x divide-zinc-100 rounded-2xl border border-zinc-100 bg-zinc-50/50 rtl:divide-x-reverse dark:divide-zinc-800 dark:border-zinc-800/60 dark:bg-zinc-800/40">
                        @foreach ([
                            ['label' => __('Users'), 'value' => $company->users_count],
                            ['label' => __('Depts'), 'value' => $company->departments_count],
                            ['label' => __('Tickets'), 'value' => $company->tickets_count],
                        ] as $stat)
                            <div class="flex flex-col items-center py-4 text-center">
                                <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-zinc-400">{{ $stat['label'] }}</span>
                                <span class="mt-1 text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ $stat['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @can('update', $company)
                    <div class="border-t border-zinc-100/80 bg-zinc-50/30 p-4 dark:border-zinc-800/60 dark:bg-zinc-900/50">
                        <flux:button size="sm" variant="ghost" wire:click="toggleStatus({{ $company->id }})" class="w-full font-bold rounded-xl hover:bg-white dark:hover:bg-zinc-800 transition-all" wire:loading.attr="disabled">
                            {{ $company->status === \App\Enums\CompanyStatus::Active ? __('Suspend company') : __('Activate company') }}
                        </flux:button>
                    </div>
                @endcan
            </div>
        @empty
            <x-empty-state icon="building-office-2" :heading="__('No companies found.')" :description="__('Get started by creating a new company or adjusting your search filters.')">
                <x-slot:action>
                    @can('create', \App\Models\Company::class)
                        <flux:button x-on:click="showForm = true" variant="primary" size="sm" icon="plus" class="font-bold rounded-xl px-6">{{ __('Create company') }}</flux:button>
                    @endcan
                </x-slot:action>
            </x-empty-state>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $companies->links() }}
    </div>
</div>
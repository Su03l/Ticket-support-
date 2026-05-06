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
                ->paginate(10),
            'statuses' => CompanyStatus::cases(),
            'plans' => Plan::query()->where('is_active', true)->orderBy('price')->get(['id', 'name']),
        ];
    }
}; ?>

<div x-data="{ showForm: false }" class="flex flex-col gap-6">
    <x-page-header :title="__('Companies')" :description="__('Manage tenant companies, subscriptions, and workspace capacity.')">
        <x-slot:actions>
            @can('create', \App\Models\Company::class)
                <flux:button x-on:click="showForm = !showForm" variant="primary" icon="plus">{{ __('Create company') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @can('create', \App\Models\Company::class)
        <div x-show="showForm" x-collapse x-cloak>
            <x-section-card :heading="__('Create company')">
                <x-slot:headerAction>
                    <button type="button" x-on:click="showForm = false" class="text-zinc-400 transition-colors hover:text-zinc-600 dark:hover:text-zinc-300">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </x-slot:headerAction>

                <form wire:submit="createCompany">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <flux:input wire:model="name" :label="__('Company name')" />
                        <flux:input wire:model="email" :label="__('Email')" type="email" />
                        <flux:input wire:model="phone" :label="__('Phone')" />
                        <flux:input wire:model="website" :label="__('Website')" type="url" />
                        <div class="md:col-span-2 lg:col-span-2">
                            <flux:select wire:model="planId" :label="__('Plan')">
                                <flux:select.option value="">{{ __('No plan') }}</flux:select.option>
                                @foreach ($plans as $plan)
                                    <flux:select.option value="{{ $plan->id }}">{{ $plan->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <flux:button type="button" x-on:click="showForm = false" variant="ghost">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('Save company') }}</flux:button>
                    </div>
                </form>
            </x-section-card>
        </div>
    @endcan

    <x-filter-bar columns="sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search companies')" />
        <flux:select wire:model.live="status">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $statusOption)
                <flux:select.option value="{{ $statusOption->value }}">{{ __(ucfirst($statusOption->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
    </x-filter-bar>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($companies as $company)
            <div wire:key="company-{{ $company->id }}" class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgb(0,0,0,0.02)] transition-all hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-md dark:border-zinc-800/80 dark:bg-zinc-900 dark:hover:border-zinc-700">
                <div class="flex items-start justify-between p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-zinc-100/80 text-lg font-bold text-zinc-500 ring-1 ring-zinc-200/50 transition-colors group-hover:bg-zinc-200/50 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700/50 dark:group-hover:bg-zinc-700/50">
                            {{ mb_substr($company->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <flux:heading size="sm" class="truncate font-semibold tracking-tight group-hover:text-zinc-900 dark:group-hover:text-white">{{ $company->name }}</flux:heading>
                            <flux:text class="mt-0.5 text-xs font-medium text-zinc-500">{{ $company->plan?->name ?? __('No plan') }}</flux:text>
                        </div>
                    </div>
                    <x-status-badge :status="$company->status->value" />
                </div>

                <div class="flex flex-1 flex-col border-t border-zinc-100/80 px-6 pb-6 pt-5 dark:border-zinc-800/80">
                    <div class="flex flex-col gap-2.5 text-sm text-zinc-600 dark:text-zinc-400">
                        <div class="flex items-center gap-2.5">
                            <flux:icon.envelope class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate">{{ $company->email ?? __('No email') }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <flux:icon.phone class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate">{{ $company->phone ?? __('Unknown') }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <flux:icon.globe-alt class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate">{{ $company->website ?? __('No website') }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-3 divide-x divide-zinc-100 rounded-lg border border-zinc-100 bg-zinc-50/80 rtl:divide-x-reverse dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-800/30">
                        @foreach ([
                            ['label' => __('Users'), 'value' => $company->users_count],
                            ['label' => __('Depts'), 'value' => $company->departments_count],
                            ['label' => __('Tickets'), 'value' => $company->tickets_count],
                        ] as $stat)
                            <div class="flex flex-col items-center py-2.5 text-center">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">{{ $stat['label'] }}</span>
                                <span class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">{{ $stat['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @can('update', $company)
                    <div class="border-t border-zinc-100/80 bg-zinc-50/50 px-6 py-3.5 dark:border-zinc-800/80 dark:bg-zinc-900/50">
                        <flux:button size="sm" variant="ghost" wire:click="toggleStatus({{ $company->id }})" class="w-full" wire:loading.attr="disabled">
                            {{ $company->status === \App\Enums\CompanyStatus::Active ? __('Suspend') : __('Activate') }}
                        </flux:button>
                    </div>
                @endcan
            </div>
        @empty
            <x-empty-state icon="building-office-2" :heading="__('No companies found.')" :description="__('Get started by creating a new company or adjusting your search filters.')">
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

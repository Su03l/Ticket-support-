<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\UserInvitationService;
use App\Services\UserManagementService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Users')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 public string $search = '';

 public string $status = '';

 public string $userType = '';

 public string $name = '';

 public string $email = '';

 public string $inviteUserType = 'support_agent';

 public string $roleName = 'support_agent';

 public string $departmentId = '';

 public string $companyId = '';

 public function mount(): void
 {
  $this->authorize('viewAny', User::class);
 }

 public function updated(string $property): void
 {
  if (in_array($property, ['search', 'status', 'userType'], true)) {
   $this->resetPage();
  }
 }

 public function invite(UserInvitationService $invitations): void
 {
  $this->authorize('create', UserInvitation::class);

  $user = Auth::user();
  $allowedTypes = $this->allowedUserTypes();

  $validated = $this->validate([
   'name'   => ['required', 'string', 'max:255'],
   'email'   => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
   'inviteUserType' => ['required', Rule::in($allowedTypes)],
   'roleName'  => ['nullable', 'string', 'max:255'],
   'departmentId' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $user->company_id ?? (int) $this->companyId)],
   'companyId'  => [$user->company_id === null ? 'required' : 'nullable', 'nullable', Rule::exists('companies', 'id')],
  ]);

  $invitations->invite($user, [
   'name'   => $validated['name'],
   'email'   => $validated['email'],
   'user_type'  => $validated['inviteUserType'],
   'role_name'  => $validated['roleName'] ?: $validated['inviteUserType'],
   'department_id' => $validated['departmentId'] ?: null,
   'company_id' => $validated['companyId'] ?: null,
  ]);

  $this->reset(['name', 'email', 'departmentId']);
  Flux::toast(variant: 'success', text: __('Invitation created.'));
 }

 public function suspend(User $managedUser, UserManagementService $users): void
 {
  $this->authorize('suspend', $managedUser);

  $users->suspend(Auth::user(), $managedUser);
  Flux::toast(variant: 'success', text: __('User suspended.'));
 }

 public function restore(User $managedUser, UserManagementService $users): void
 {
  $this->authorize('restore', $managedUser);

  $users->restore(Auth::user(), $managedUser);
  Flux::toast(variant: 'success', text: __('User restored.'));
 }

 public function resend(UserInvitation $invitation, UserInvitationService $invitations): void
 {
  $this->authorize('resend', $invitation);

  $invitations->resend(Auth::user(), $invitation);
  Flux::toast(variant: 'success', text: __('Invitation resent.'));
 }

 public function with(UserManagementService $users, UserInvitationService $invitations): array
 {
  $user = Auth::user();

  return [
   'users' => $users->listForManager($user, [
    'search' => $this->search ?: null,
    'status' => $this->status ?: null,
    'user_type' => $this->userType ?: null,
   ]),
   'invitations' => $invitations->listForManager($user),
   'statuses' => UserStatus::cases(),
   'userTypes' => $this->allowedUserTypes(),
   'departments' => Department::query()
    ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
    ->orderBy('name')
    ->get(['id', 'name']),
   'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
   'roles'  => Role::query()
    ->when($user->company_id !== null, fn ($query) => $query->where('name', '!=', UserType::SuperAdmin->value))
    ->orderBy('name')
    ->get(['name']),
  ];
 }

 /**
  * @return array<int, string>
  */
 private function allowedUserTypes(): array
 {
  $types = [
   UserType::CompanyAdmin->value,
   UserType::DepartmentManager->value,
   UserType::DepartmentDeputy->value,
   UserType::SupportAgent->value,
   UserType::Customer->value,
  ];

  if (Auth::user()->company_id === null) {
   array_unshift($types, UserType::SuperAdmin->value);
  }

  return $types;
 }
}; ?>

<div x-data="{ showInvite: false }" class="flex flex-col gap-6">
 <x-page-header :title="__('Users')":description="__('Invite employees, assign departments, and manage account status.')">
  <x-slot:actions>
   @can('create', \App\Models\UserInvitation::class)
    <flux:button x-on:click="showInvite = !showInvite" variant="primary" icon="user-plus">{{ __('Invite user') }}</flux:button>
   @endcan
  </x-slot:actions>
 </x-page-header>

 {{-- Invite panel --}}
 @can('create', \App\Models\UserInvitation::class)
  <div x-show="showInvite"x-collapse x-cloak>
   <x-section-card :heading="__('Invite user')" icon="envelope">
    <x-slot:headerAction>
     <button type="button"x-on:click="showInvite = false" class="flex size-8 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
      <flux:icon.x-mark class="size-4"/>
     </button>
    </x-slot:headerAction>

    <form wire:submit="invite">
     <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <flux:input wire:model="name":label="__('Name')":placeholder="__('Full name')"required />
      <flux:input wire:model="email":label="__('Email')" type="email":placeholder="__('work@company.com')"required />
      @if (auth()->user()->company_id === null)
       <flux:select wire:model="companyId":label="__('Company')">
        <flux:select.option value="">{{ __('Select company') }}</flux:select.option>
        @foreach ($companies as $company)
         <flux:select.option value="{{ $company->id }}">{{ $company->name }}</flux:select.option>
        @endforeach
       </flux:select>
      @endif
      <flux:select wire:model="inviteUserType":label="__('User type')">
       @foreach ($userTypes as $type)
        <flux:select.option value="{{ $type }}">{{ __(str_replace('_', ' ', $type)) }}</flux:select.option>
       @endforeach
      </flux:select>
      <flux:select wire:model="roleName":label="__('Role')">
       @foreach ($roles as $role)
        <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
       @endforeach
      </flux:select>
      <flux:select wire:model="departmentId":label="__('Department')">
       <flux:select.option value="">{{ __('No department') }}</flux:select.option>
       @foreach ($departments as $department)
        <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>
     <div class="mt-5 flex items-center justify-end gap-3 border-t border-zinc-100/80 pt-4 dark:border-zinc-800">
      <flux:button type="button"x-on:click="showInvite = false" variant="ghost">{{ __('Cancel') }}</flux:button>
      <flux:button variant="primary" type="submit">{{ __('Send invitation') }}</flux:button>
     </div>
    </form>
   </x-section-card>
  </div>
 @endcan

 {{-- Filters --}}
 <x-filter-bar columns="sm:grid-cols-3">
  <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass":placeholder="__('Search users...')"/>
  <flux:select wire:model.live="status">
   <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
   @foreach ($statuses as $statusOption)
    <flux:select.option value="{{ $statusOption->value }}">{{ __(ucfirst($statusOption->value)) }}</flux:select.option>
   @endforeach
  </flux:select>
  <flux:select wire:model.live="userType">
   <flux:select.option value="">{{ __('All user types') }}</flux:select.option>
   @foreach ($userTypes as $type)
    <flux:select.option value="{{ $type }}">{{ __(str_replace('_', ' ', $type)) }}</flux:select.option>
   @endforeach
  </flux:select>
 </x-filter-bar>

 {{-- Users list --}}
 <div class="card overflow-hidden">
  {{-- Table header --}}
  <div class="hidden border-b border-zinc-100/80 bg-zinc-50/80 px-6 py-3 dark:border-zinc-800/80 dark:bg-zinc-800/30 lg:grid lg:grid-cols-[1fr_10rem_8rem_12rem] lg:items-center">
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('User') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Type') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Status') }}</span>
   <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 text-right">{{ __('Actions') }}</span>
  </div>

  @forelse ($users as $managedUser)
   <x-list-row wire:key="user-{{ $managedUser->id }}" class="group px-6 lg:grid-cols-[1fr_10rem_8rem_12rem] lg:items-center">
    <div class="flex items-center gap-3">
     @php
      $avatarColors = ['bg-blue-100 text-blue-700', 'bg-emerald-100 text-emerald-700', 'bg-violet-100 text-violet-700', 'bg-amber-100 text-amber-700', 'bg-rose-100 text-rose-700'];
      $avatarColor = $avatarColors[abs(crc32($managedUser->name)) % count($avatarColors)];
     @endphp
     <div class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $avatarColor }}">
      {{ mb_substr($managedUser->name, 0, 1) }}
     </div>
     <div class="min-w-0">
      <p class="text-sm font-semibold text-zinc-800 group-hover:text-zinc-900 dark:text-zinc-200 dark:group-hover:text-white">{{ $managedUser->name }}</p>
      <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $managedUser->email }}</p>
     </div>
    </div>
    <x-status-badge :status="str_replace('_', ' ', $managedUser->user_type->value)"/>
    <x-status-badge :status="$managedUser->status->value"/>
    <div class="flex gap-2 lg:justify-end">
     @can('suspend', $managedUser)
      @if ($managedUser->status !== \App\Enums\UserStatus::Suspended)
       <flux:button size="sm" variant="ghost" wire:click="suspend({{ $managedUser->id }})">{{ __('Suspend') }}</flux:button>
      @endif
     @endcan
     @can('restore', $managedUser)
      @if ($managedUser->status !== \App\Enums\UserStatus::Active)
       <flux:button size="sm" variant="ghost" wire:click="restore({{ $managedUser->id }})">{{ __('Activate') }}</flux:button>
      @endif
     @endcan
    </div>
   </x-list-row>
  @empty
   <x-empty-state icon="users":heading="__('No users found.')" class="py-14"/>
  @endforelse
 </div>

 <div class="mt-2">
  {{ $users->links() }}
 </div>

 {{-- Pending invitations --}}
 @if ($invitations->isNotEmpty())
  <x-section-card :heading="__('Pending invitations')" icon="envelope-open">
   <div class="flex flex-col divide-y divide-zinc-100/80 dark:divide-zinc-800/80">
    @foreach ($invitations as $invitation)
     <div wire:key="invitation-{{ $invitation->id }}" class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
      <div>
       <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $invitation->email }}</p>
       <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ __('Expires') }} {{ $invitation->expires_at->diffForHumans() }}
       </p>
      </div>
      @can('resend', $invitation)
       <flux:button size="sm" variant="ghost" wire:click="resend({{ $invitation->id }})">{{ __('Resend') }}</flux:button>
      @endcan
     </div>
    @endforeach
   </div>
  </x-section-card>
 @endif
</div>

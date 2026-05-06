<?php

use App\Services\PermissionManagementService;
use App\Services\RoleManagementService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Title('Roles')] class extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $permissionSearch = '';

    public ?int $selectedRoleId = null;

    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
        $this->selectedRoleId = Role::query()->orderBy('name')->value('id');

        if ($this->selectedRoleId !== null) {
            $this->selectRole($this->selectedRoleId);
        }
    }

    public function createRole(RoleManagementService $roles): void
    {
        $this->authorize('create', Role::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $role = $roles->create(Auth::user(), $validated['name']);
        $this->selectRole($role->id);
        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Role created.'));
    }

    public function selectRole(int $roleId): void
    {
        $role = Role::query()->with('permissions:id,name')->findOrFail($roleId);
        $this->authorize('update', $role);

        $this->selectedRoleId = $role->id;
        $this->selectedPermissions = $role->permissions->pluck('name')->values()->all();
    }

    public function savePermissions(RoleManagementService $roles): void
    {
        $role = Role::query()->findOrFail($this->selectedRoleId);
        $this->authorize('update', $role);

        $roles->syncPermissions(Auth::user(), $role, $this->selectedPermissions);

        Flux::toast(variant: 'success', text: __('Permissions updated.'));
    }

    public function deleteRole(RoleManagementService $roles, int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->authorize('delete', $role);

        $roles->delete(Auth::user(), $role);

        if ($this->selectedRoleId === $roleId) {
            $this->reset(['selectedRoleId', 'selectedPermissions']);
        }

        Flux::toast(variant: 'success', text: __('Role deleted.'));
    }

    public function with(RoleManagementService $roles, PermissionManagementService $permissions): array
    {
        return [
            'roles' => $roles->all(),
            'permissionGroups' => $permissions->grouped($this->permissionSearch ?: null),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Roles') }}</flux:heading>
        <flux:text>{{ __('Manage role permissions and protected system roles.') }}</flux:text>
    </div>

    @can('create', \Spatie\Permission\Models\Role::class)
        <form wire:submit="createRole" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:input class="flex-1" wire:model="name" :label="__('New role')" required />
                <flux:button variant="primary" type="submit" icon="plus">{{ __('Create role') }}</flux:button>
            </div>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white p-2 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex min-w-max gap-2">
            @foreach ($roles as $role)
                <div wire:key="role-tab-{{ $role->id }}" class="flex items-center gap-1 rounded-lg {{ $selectedRoleId === $role->id ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-50 text-zinc-700 dark:bg-zinc-950 dark:text-zinc-200' }}">
                    <button type="button" wire:click="selectRole({{ $role->id }})" class="px-4 py-2 text-sm font-medium">
                        {{ $role->name }}
                        <span class="ms-2 text-xs opacity-70">{{ $role->permissions_count ?? $role->permissions->count() }}</span>
                    </button>
                    @can('delete', $role)
                        <button type="button" wire:click="deleteRole({{ $role->id }})" class="me-2 rounded p-1 text-xs hover:bg-black/10 dark:hover:bg-white/10" aria-label="{{ __('Delete role') }}">×</button>
                    @endcan
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        @if ($selectedRoleId)
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">{{ __('Permissions') }}</flux:heading>
                    <flux:text>{{ __('Group, search, and assign granular permissions.') }}</flux:text>
                </div>
                <flux:button variant="primary" wire:click="savePermissions">{{ __('Save permissions') }}</flux:button>
            </div>

            <flux:input class="mt-4" wire:model.live.debounce.300ms="permissionSearch" icon="magnifying-glass" :placeholder="__('Search permissions')" />

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                @foreach ($permissionGroups as $module => $permissions)
                    <div wire:key="permission-group-{{ $module }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                        <flux:heading size="sm">{{ __(str_replace('_', ' ', $module)) }}</flux:heading>
                        <div class="mt-3 grid gap-2">
                            @foreach ($permissions as $permission)
                                <flux:checkbox wire:model="selectedPermissions" value="{{ $permission->name }}" :label="__($permission->name)" wire:key="permission-{{ $permission->id }}" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-10 text-center">
                <flux:heading size="md">{{ __('Select a role') }}</flux:heading>
                <flux:text>{{ __('Role permissions will appear here.') }}</flux:text>
            </div>
        @endif
    </div>
</div>

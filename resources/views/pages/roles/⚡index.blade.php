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

<div class="flex flex-col gap-10">
    <x-page-header :title="__('Roles & Permissions')" :description="__('Manage role permissions and protected system roles.')" />

    @can('create', \Spatie\Permission\Models\Role::class)
        <x-section-card :heading="__('Create new role')" icon="shield-check" compact>
            <form wire:submit="createRole" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <flux:input class="flex-1" wire:model="name" :label="__('Role name')" placeholder="e.g. Supervisor" required />
                <flux:button variant="primary" type="submit" icon="plus" class="font-bold rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Create role') }}</flux:button>
            </form>
        </x-section-card>
    @endcan

    <div class="overflow-x-auto rounded-[2rem] border border-zinc-200/60 bg-white p-3 shadow-sm dark:border-zinc-800/60 dark:bg-zinc-900">
        <div class="flex min-w-max gap-3 px-1">
            @foreach ($roles as $role)
                <div wire:key="role-tab-{{ $role->id }}" class="flex items-center gap-1 rounded-2xl transition-all {{ $selectedRoleId === $role->id ? 'bg-zinc-900 text-white shadow-lg dark:bg-white dark:text-zinc-900' : 'bg-zinc-50 text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800/40 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                    <button type="button" wire:click="selectRole({{ $role->id }})" class="px-6 py-2.5 text-sm font-bold tracking-tight">
                        {{ $role->name }}
                        <span class="ms-2 inline-flex items-center justify-center rounded-lg px-1.5 py-0.5 text-[10px] font-black {{ $selectedRoleId === $role->id ? 'bg-white/20 text-white dark:bg-black/10 dark:text-zinc-900' : 'bg-zinc-200/60 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-500' }}">
                            {{ $role->permissions_count ?? $role->permissions->count() }}
                        </span>
                    </button>
                    @can('delete', $role)
                        <button type="button" wire:click="deleteRole({{ $role->id }})" class="me-3 rounded-lg p-1.5 transition-colors hover:bg-red-500/20 hover:text-red-500" aria-label="{{ __('Delete role') }}">
                            <flux:icon.x-mark class="size-4" />
                        </button>
                    @endcan
                </div>
            @endforeach
        </div>
    </div>

    @if ($selectedRoleId)
        <x-section-card icon="key">
            <x-slot:heading>
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Permissions for') }} {{ $roles->firstWhere('id', $selectedRoleId)?->name }}</span>
                    <flux:text class="text-xs font-medium">{{ __('Assign granular module-based access.') }}</flux:text>
                </div>
            </x-slot:heading>

            <x-slot:headerAction>
                <flux:button variant="primary" wire:click="savePermissions" class="font-bold rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Save permissions') }}</flux:button>
            </x-slot:headerAction>

            <div class="flex flex-col gap-8">
                <flux:input wire:model.live.debounce.300ms="permissionSearch" icon="magnifying-glass" :placeholder="__('Search permissions...')" class="max-w-md" />

                <div class="grid gap-8 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($permissionGroups as $module => $permissions)
                        <div wire:key="permission-group-{{ $module }}" class="rounded-2xl border border-zinc-100 bg-zinc-50/30 p-6 dark:border-zinc-800/60 dark:bg-zinc-800/20 transition-all hover:bg-white hover:shadow-md dark:hover:bg-zinc-800/40">
                            <flux:heading size="sm" class="font-bold tracking-wider uppercase text-blue-600 dark:text-blue-400 border-b border-zinc-100 dark:border-zinc-800 pb-3 mb-4">{{ __(str_replace('_', ' ', $module)) }}</flux:heading>
                            <div class="grid gap-3">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <flux:checkbox wire:model="selectedPermissions" value="{{ $permission->name }}" wire:key="permission-{{ $permission->id }}" />
                                        <span class="text-sm font-semibold text-zinc-600 group-hover:text-zinc-900 dark:text-zinc-400 dark:group-hover:text-white transition-colors">{{ __($permission->name) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex justify-end border-t border-zinc-100/80 pt-8 dark:border-zinc-800">
                    <flux:button variant="primary" wire:click="savePermissions" class="font-bold rounded-xl px-10 shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('Save changes') }}</flux:button>
                </div>
            </div>
        </x-section-card>
    @else
        <x-empty-state icon="shield-check" :heading="__('Select a role')" :description="__('Click on a role above to manage its permissions and system access.')" />
    @endif
</div>
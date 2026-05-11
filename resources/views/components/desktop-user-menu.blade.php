<flux:dropdown position="bottom" align="start" {{ $attributes }}>
  <flux:sidebar.profile
    :name="auth()->user()->name"
    :initials="auth()->user()->initials()"
    icon:trailing="chevrons-up-down"
    data-test="sidebar-menu-button"
    class="rounded-xl transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
  />

  <flux:menu class="min-w-64 rounded-2xl p-2 shadow-xl shadow-zinc-200/40 dark:shadow-zinc-900/50">
    <div class="flex items-center gap-4 px-4 py-4">
      <flux:avatar
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        class="size-11 ring-2 ring-zinc-100 dark:ring-zinc-800"
      />
      <div class="grid min-w-0 flex-1 text-start leading-snug">
        <span class="truncate text-base font-bold tracking-tight text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
        <span class="truncate text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
      </div>
    </div>

    <flux:menu.separator class="my-1" />

    <flux:menu.radio.group>
      <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate class="rounded-xl px-3 py-2.5 font-medium">
        {{ __('Profile') }}
      </flux:menu.item>
      <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate class="rounded-xl px-3 py-2.5 font-medium">
        {{ __('Account settings') }}
      </flux:menu.item>
    </flux:menu.radio.group>

    <flux:menu.separator class="my-1" />

    <form method="POST" action="{{ route('logout') }}" class="w-full">
      @csrf
      <flux:menu.item
        as="button"
        type="submit"
        icon="arrow-right-start-on-rectangle"
        class="w-full cursor-pointer rounded-xl px-3 py-2.5 font-bold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
        data-test="logout-button"
      >
        {{ __('Log out') }}
      </flux:menu.item>
    </form>
  </flux:menu>
</flux:dropdown>
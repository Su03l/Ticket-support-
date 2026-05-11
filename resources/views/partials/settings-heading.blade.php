<div class="card mb-6 overflow-hidden">
  <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
    <div>
      <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Settings') }}</h1>
      <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Manage profile, security, appearance, and company preferences') }}</p>
    </div>

    <div class="flex items-center gap-3 rounded-xl border border-zinc-100/80 bg-zinc-50/80 px-4 py-2.5 dark:border-zinc-800/80 dark:bg-zinc-900/60">
      <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" class="ring-2 ring-zinc-200/80 dark:ring-zinc-700" />
      <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->name }}</p>
        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</p>
      </div>
    </div>
  </div>
</div>

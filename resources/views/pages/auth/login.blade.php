<x-layouts::auth :title="__('Log in')">
  <div class="flex flex-col gap-6">
    <x-auth-header :title="__('Sign in to your workspace')" :description="__('Manage support work, mailbox updates, and customer requests.')" />

    <x-auth-session-status class="text-center font-semibold" :status="session('status')" />

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
      @csrf

      <flux:input
        name="email"
        icon="envelope"
        :label="__('Email address')"
        :value="old('email')"
        type="email"
        required
        autofocus
        autocomplete="email"
        placeholder="email@example.com"
      />

      <div class="relative">
        <flux:input
          name="password"
          icon="lock-closed"
          :label="__('Password')"
          type="password"
          required
          autocomplete="current-password"
          :placeholder="__('Password')"
          viewable
        />

        @if (Route::has('password.request'))
          <flux:link class="absolute top-0 text-xs font-semibold end-0" :href="route('password.request')" wire:navigate>
            {{ __('Forgot your password?') }}
          </flux:link>
        @endif
      </div>

      <div class="flex items-center justify-between gap-4">
        <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" class="font-medium" />
        <flux:badge color="emerald" size="sm" class="font-semibold px-2 py-0.5">{{ __('Protected') }}</flux:badge>
      </div>

      <div class="flex items-center justify-end">
        <flux:button variant="primary" icon="arrow-right-end-on-rectangle" type="submit" class="w-full font-semibold py-2.5 rounded-xl shadow-md shadow-zinc-200/40 dark:shadow-none" data-test="login-button">
          {{ __('Log in') }}
        </flux:button>
      </div>
    </form>

    @if (Route::has('register'))
      <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center text-sm font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
        <span>{{ __('Don\'t have an account?') }}</span>
        <flux:link :href="route('register')" wire:navigate class="font-semibold ml-1">{{ __('Sign up') }}</flux:link>
      </div>
    @endif
  </div>
</x-layouts::auth>
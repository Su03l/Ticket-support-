<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-lg bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                    <x-app-logo-icon class="size-6 fill-current" />
                </span>
                <div class="min-w-0">
                    <flux:heading size="lg">{{ __('Welcome back') }}</flux:heading>
                    <flux:text class="truncate text-sm">{{ config('app.name', 'Enterprise Support Desk') }}</flux:text>
                </div>
            </div>
        </div>

        <x-auth-header :title="__('Sign in to your workspace')" :description="__('Manage support work, mailbox updates, and customer requests.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

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
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3">
                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />
                <flux:badge color="emerald" size="sm">{{ __('Protected') }}</flux:badge>
            </div>

            <div class="flex items-center justify-end">
                <flux:button variant="primary" icon="arrow-right-end-on-rectangle" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="rounded-lg border border-zinc-200 bg-white p-3 text-center text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>

<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-8">
        <div class="rounded-2xl border border-zinc-200/60 bg-zinc-50/50 p-5 dark:border-zinc-800/60 dark:bg-zinc-950/50">
            <div class="flex items-center gap-4">
                <span class="flex size-12 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-lg dark:bg-white dark:text-zinc-950">
                    <x-app-logo-icon class="size-7 fill-current" />
                </span>
                <div class="min-w-0">
                    <flux:heading size="lg" class="font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Welcome back') }}</flux:heading>
                    <flux:text class="truncate text-sm font-medium text-zinc-500">{{ config('app.name', 'Enterprise Support Desk') }}</flux:text>
                </div>
            </div>
        </div>

        <x-auth-header :title="__('Sign in to your workspace')" :description="__('Manage support work, mailbox updates, and customer requests.')" />

        <x-auth-session-status class="text-center font-semibold" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
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
                    <flux:link class="absolute top-0 text-xs font-bold end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center justify-between gap-4">
                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" class="font-medium" />
                <flux:badge color="emerald" size="sm" class="font-bold px-2 py-0.5">{{ __('Protected') }}</flux:badge>
            </div>

            <div class="flex items-center justify-end mt-2">
                <flux:button variant="primary" icon="arrow-right-end-on-rectangle" type="submit" class="w-full font-bold py-3 rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="rounded-xl border border-zinc-200/80 bg-white p-4 text-center text-sm font-medium text-zinc-600 dark:border-zinc-800/80 dark:bg-zinc-900/50 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="font-bold ml-1">{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
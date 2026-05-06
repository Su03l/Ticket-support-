<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-lg bg-zinc-950 text-white dark:bg-white dark:text-zinc-950">
                    <x-app-logo-icon class="size-6 fill-current" />
                </span>
                <div class="min-w-0">
                    <flux:heading size="lg">{{ __('Create customer account') }}</flux:heading>
                    <flux:text class="truncate text-sm">{{ config('app.name', 'Enterprise Support Desk') }}</flux:text>
                </div>
            </div>
        </div>

        <x-auth-header :title="__('Start your support portal')" :description="__('Public registration is for customers. Team members are invited by admins.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="name"
                icon="user"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <flux:input
                name="email"
                icon="envelope"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                name="password"
                icon="lock-closed"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:input
                name="password_confirmation"
                icon="lock-closed"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ __('Your account will be created as a customer profile with access to your own tickets, complaints, inquiries, and knowledge base articles.') }}
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" icon="user-plus" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="rounded-lg border border-zinc-200 bg-white p-3 text-center text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>

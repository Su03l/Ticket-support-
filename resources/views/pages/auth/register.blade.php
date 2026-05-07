<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-8">
        <div class="rounded-2xl border border-zinc-200/60 bg-zinc-50/50 p-5 dark:border-zinc-800/60 dark:bg-zinc-950/50">
            <div class="flex items-center gap-4">
                <span class="flex size-12 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-lg dark:bg-white dark:text-zinc-950">
                    <x-app-logo-icon class="size-7 fill-current" />
                </span>
                <div class="min-w-0">
                    <flux:heading size="lg" class="font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Create customer account') }}</flux:heading>
                    <flux:text class="truncate text-sm font-medium text-zinc-500">{{ config('app.name', 'Enterprise Support Desk') }}</flux:text>
                </div>
            </div>
        </div>

        <x-auth-header :title="__('Start your support portal')" :description="__('Public registration is for customers. Team members are invited by admins.')" />

        <x-auth-session-status class="text-center font-semibold" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
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

            <div class="rounded-xl border border-emerald-200/60 bg-emerald-50/50 p-4 text-sm font-medium leading-relaxed text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                {{ __('Your account will be created as a customer profile with access to your own tickets, complaints, inquiries, and knowledge base articles.') }}
            </div>

            <div class="flex items-center justify-end mt-2">
                <flux:button type="submit" variant="primary" icon="user-plus" class="w-full font-bold py-3 rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="rounded-xl border border-zinc-200/80 bg-white p-4 text-center text-sm font-medium text-zinc-600 dark:border-zinc-800/80 dark:bg-zinc-900/50 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate class="font-bold ml-1">{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
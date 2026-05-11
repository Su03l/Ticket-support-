<x-layouts::auth :title="__('Register')">
  <div class="flex flex-col gap-6">
    <x-auth-header :title="__('Start your support portal')" :description="__('Public registration is for customers. Team members are invited by admins.')" />

    <x-auth-session-status class="text-center font-semibold" :status="session('status')" />

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

      <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium leading-relaxed text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
        {{ __('Your account will be created as a customer profile with access to your own tickets, complaints, inquiries, and knowledge base articles.') }}
      </div>

      <div class="flex items-center justify-end">
        <flux:button type="submit" variant="primary" icon="user-plus" class="w-full font-semibold py-2.5 rounded-xl shadow-md shadow-zinc-200/40 dark:shadow-none" data-test="register-user-button">
          {{ __('Create account') }}
        </flux:button>
      </div>
    </form>

    <div class="rounded-xl border border-zinc-200 bg-white p-3 text-center text-sm font-medium text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
      <span>{{ __('Already have an account?') }}</span>
      <flux:link :href="route('login')" wire:navigate class="font-semibold ml-1">{{ __('Log in') }}</flux:link>
    </div>
  </div>
</x-layouts::auth>
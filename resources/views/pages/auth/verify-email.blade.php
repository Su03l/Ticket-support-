<x-layouts::auth :title="__('Email verification')">
  <div class="flex flex-col gap-8">
    <x-auth-header :title="__('Verify your email')" :description="__('Please verify your email address by clicking on the link we just emailed to you.')" />

    @if (session('status') == 'verification-link-sent')
      <div class="rounded-xl border border-emerald-200/60 bg-emerald-50/50 p-4 text-center text-sm font-bold text-emerald-600 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-400">
        {{ __('A new verification link has been sent to the email address you provided during registration.') }}
      </div>
    @endif

    <div class="flex flex-col items-center justify-between gap-6">
      <form method="POST" action="{{ route('verification.send') }}" class="w-full">
        @csrf
        <flux:button type="submit" variant="primary" class="w-full font-bold py-3 rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none">
          {{ __('Resend verification email') }}
        </flux:button>
      </form>

      <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <flux:button variant="ghost" type="submit" class="w-full font-bold text-sm cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800" data-test="logout-button">
          {{ __('Log out') }}
        </flux:button>
      </form>
    </div>
  </div>
</x-layouts::auth>
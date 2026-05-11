<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <head>
    @include('partials.head')
  </head>
  <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
    @php($alternateLocale = app()->getLocale() === 'ar' ? 'en' : 'ar')

    <form method="POST" action="{{ route('language.switch', $alternateLocale) }}" class="fixed right-6 top-6 z-20 rtl:left-6 rtl:right-auto">
      @csrf
      <button type="submit" class="rounded-lg border border-zinc-200 bg-white/90 px-4 py-2 text-sm font-semibold text-zinc-900 shadow-sm backdrop-blur-md transition-all hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/90 dark:text-white dark:hover:bg-zinc-800">
        {{ $alternateLocale === 'ar' ? __('Arabic') : __('English') }}
      </button>
    </form>

    <div class="flex min-h-svh items-center justify-center p-6 md:p-12">
      <div class="w-full max-w-sm">
        <a href="{{ route('home') }}" class="mb-10 flex items-center justify-center gap-3 font-semibold text-zinc-900 dark:text-white text-xl" wire:navigate>
          <span class="flex size-10 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-lg dark:bg-white dark:text-zinc-950">
            <x-app-logo-icon class="size-5 fill-current" />
          </span>
          <span>{{ config('app.name', 'Support Desk') }}</span>
        </a>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-lg shadow-zinc-200/30 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none sm:p-8">
          {{ $slot }}
        </div>
      </div>
    </div>

    @persist('toast')
      <flux:toast.group>
        <flux:toast />
      </flux:toast.group>
    @endpersist

    @fluxScripts
  </body>
</html>
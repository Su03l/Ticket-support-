<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
  <head>
    @include('partials.head')
  </head>
  <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
      <div class="bg-muted relative hidden h-full flex-col p-16 text-white lg:flex dark:border-e dark:border-neutral-800">
        <div class="absolute inset-0 bg-neutral-900"></div>
        <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-3 text-xl font-semibold" wire:navigate>
          <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/20 backdrop-blur-md">
            <x-app-logo-icon class="h-5 w-5 fill-current text-white" />
          </span>
          <span>{{ config('app.name', 'Laravel') }}</span>
        </a>

        @php
          [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
        @endphp

        <div class="relative z-20 mt-auto max-w-2xl">
          <blockquote class="space-y-6">
            <flux:heading size="xl" class="text-3xl font-semibold leading-snug">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
            <footer><flux:heading size="lg" class="text-zinc-400">{{ trim($author) }}</flux:heading></footer>
          </blockquote>
        </div>
      </div>
      <div class="w-full lg:p-12">
        <div class="mx-auto flex w-full flex-col justify-center space-y-8 sm:w-[400px]">
          <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-3 font-semibold text-xl lg:hidden" wire:navigate>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-white shadow-lg dark:bg-white dark:text-zinc-950">
              <x-app-logo-icon class="size-5 fill-current" />
            </span>

            <span>{{ config('app.name', 'Laravel') }}</span>
          </a>

          <div class="w-full rounded-2xl p-6 sm:p-8 border border-zinc-200 shadow-lg shadow-zinc-200/30 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none bg-white">
            {{ $slot }}
          </div>
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
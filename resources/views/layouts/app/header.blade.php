<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
  <head>
    @include('partials.head')
  </head>
  <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 selection:bg-zinc-900 selection:text-white dark:selection:bg-white dark:selection:text-zinc-900">
    <flux:header container class="sticky top-0 z-20 border-b border-zinc-200/80 bg-white/95 py-3 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-950/95">
      <flux:sidebar.toggle class="mr-3 lg:hidden" icon="bars-2" inset="left" />

      <x-app-logo href="{{ route('dashboard') }}" wire:navigate class="scale-105 transform transition-transform" />

      <flux:navbar class="-mb-px ml-6 max-lg:hidden gap-6">
        <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="text-sm font-semibold tracking-tight">
          {{ __('Dashboard') }}
        </flux:navbar.item>
      </flux:navbar>

      <flux:spacer />

      <flux:navbar class="me-2 space-x-1 rtl:space-x-reverse py-0!">
        <flux:tooltip :content="__('Search')" position="bottom">
          <flux:navbar.item class="!h-10 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 [&>div>svg]:size-5 transition-colors" icon="magnifying-glass" href="#" :label="__('Search')" />
        </flux:tooltip>
        <flux:tooltip :content="__('Repository')" position="bottom">
          <flux:navbar.item
            class="h-10 rounded-full max-lg:hidden hover:bg-zinc-100 dark:hover:bg-zinc-800 [&>div>svg]:size-5 transition-colors"
            icon="folder-git-2"
            href="https://github.com/laravel/livewire-starter-kit"
            target="_blank"
            :label="__('Repository')"
          />
        </flux:tooltip>
        <flux:tooltip :content="__('Documentation')" position="bottom">
          <flux:navbar.item
            class="h-10 rounded-full max-lg:hidden hover:bg-zinc-100 dark:hover:bg-zinc-800 [&>div>svg]:size-5 transition-colors"
            icon="book-open-text"
            href="https://laravel.com/docs/starter-kits#livewire"
            target="_blank"
            :label="__('Documentation')"
          />
        </flux:tooltip>
      </flux:navbar>

      <x-desktop-user-menu />
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200/80 bg-white/95 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-900/95">
      <flux:sidebar.header class="border-b border-zinc-200/80 p-4 dark:border-zinc-800/80">
        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
      </flux:sidebar.header>

      <flux:sidebar.nav class="px-2 py-4">
        <flux:sidebar.group :heading="__('Platform')">
          <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="font-medium rounded-xl">
            {{ __('Dashboard') }}
          </flux:sidebar.item>
        </flux:sidebar.group>
      </flux:sidebar.nav>

      <flux:spacer />

      <flux:sidebar.nav class="px-2 py-4">
        <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="font-medium rounded-xl">
          {{ __('Repository') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="font-medium rounded-xl">
          {{ __('Documentation') }}
        </flux:sidebar.item>
      </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main class="bg-zinc-50 dark:bg-zinc-950">
      <div class="mx-auto flex w-full max-w-[85rem] flex-1 flex-col gap-8 p-6 sm:p-8 lg:p-10">
        {{ $slot }}
      </div>
    </flux:main>

    @persist('toast')
      <flux:toast.group>
        <flux:toast />
      </flux:toast.group>
    @endpersist

    @fluxScripts
  </body>
</html>
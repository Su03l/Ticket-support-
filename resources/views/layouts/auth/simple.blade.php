<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-950 antialiased dark:bg-zinc-950">
        @php($alternateLocale = app()->getLocale() === 'ar' ? 'en' : 'ar')

        <form method="POST" action="{{ route('language.switch', $alternateLocale) }}" class="fixed right-4 top-4 z-20 rtl:left-4 rtl:right-auto">
            @csrf
            <button type="submit" class="rounded-lg border border-white/15 bg-white/90 px-3 py-2 text-sm font-medium text-zinc-900 shadow-sm backdrop-blur-sm transition hover:bg-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800">
                {{ $alternateLocale === 'ar' ? __('Arabic') : __('English') }}
            </button>
        </form>

        <div class="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_minmax(28rem,34rem)]">
            {{-- ── Left hero panel ── --}}
            <section class="relative hidden overflow-hidden bg-zinc-950 text-white lg:flex">
                {{-- Multi-layer gradient backdrop --}}
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_20%,rgba(16,185,129,0.20),transparent_40%),radial-gradient(ellipse_at_80%_10%,rgba(59,130,246,0.18),transparent_40%),radial-gradient(ellipse_at_50%_80%,rgba(139,92,246,0.12),transparent_45%)]"></div>

                {{-- Dot grid overlay --}}
                <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 28px 28px;"></div>

                <div class="relative flex w-full flex-col justify-between p-10 xl:p-14">
                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold" wire:navigate>
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20 backdrop-blur-sm">
                            <x-app-logo-icon class="size-5 fill-current" />
                        </span>
                        <span class="text-white">{{ config('app.name', 'Support Desk') }}</span>
                    </a>

                    {{-- Hero content --}}
                    <div class="max-w-lg">
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1.5">
                            <span class="size-1.5 rounded-full bg-emerald-400"></span>
                            <span class="text-xs font-semibold text-emerald-300">{{ __('Enterprise support desk') }}</span>
                        </div>

                        <h1 class="text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                            {{ __('Customer support operations,') }}
                            <span class="bg-gradient-to-r from-emerald-400 to-blue-400 bg-clip-text text-transparent">
                                {{ __('secured for every company.') }}
                            </span>
                        </h1>

                        <p class="mt-5 max-w-md text-base leading-7 text-zinc-300/80">
                            {{ __('Tickets, complaints, inquiries, notifications, mailbox, and SLA signals all work inside a tenant-safe SaaS workspace.') }}
                        </p>
                    </div>

                    {{-- Feature stats --}}
                    <div class="grid max-w-md grid-cols-3 gap-3">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                            <p class="text-2xl font-bold">24/7</p>
                            <p class="mt-1 text-xs font-medium text-zinc-400">{{ __('Realtime ready') }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                            <p class="text-2xl font-bold">{{ __('SLA') }}</p>
                            <p class="mt-1 text-xs font-medium text-zinc-400">{{ __('Breach tracking') }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                            <p class="text-2xl font-bold">{{ __('Roles') }}</p>
                            <p class="mt-1 text-xs font-medium text-zinc-400">{{ __('Scoped access') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Right form panel ── --}}
            <section class="flex min-h-svh flex-col items-center justify-center bg-zinc-50 p-6 dark:bg-zinc-950 sm:p-8">
                <div class="w-full max-w-md">
                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center gap-3 font-semibold text-zinc-900 dark:text-white lg:hidden" wire:navigate>
                        <span class="flex size-10 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-950">
                            <x-app-logo-icon class="size-5 fill-current" />
                        </span>
                        <span>{{ config('app.name', 'Support Desk') }}</span>
                    </a>

                    {{-- Auth form card --}}
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-7 shadow-[0_4px_24px_0_rgb(0,0,0,0.06)] dark:border-zinc-800/80 dark:bg-zinc-900 sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-5 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Protected by tenant-scoped roles, policies, and secure sessions.') }}
                    </p>
                </div>
            </section>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

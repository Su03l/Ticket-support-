<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-950 antialiased dark:bg-zinc-950">
        @php($alternateLocale = app()->getLocale() === 'ar' ? 'en' : 'ar')

        <form method="POST" action="{{ route('language.switch', $alternateLocale) }}" class="fixed right-6 top-6 z-20 rtl:left-6 rtl:right-auto">
            @csrf
            <button type="submit" class="rounded-xl border border-white/15 bg-white/90 px-4 py-2.5 text-sm font-bold tracking-tight text-zinc-900 shadow-sm backdrop-blur-md transition-all hover:bg-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:hover:bg-zinc-800">
                {{ $alternateLocale === 'ar' ? __('Arabic') : __('English') }}
            </button>
        </form>

        <div class="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_minmax(32rem,40rem)]">
            {{-- ── Left hero panel ── --}}
            <section class="relative hidden overflow-hidden bg-zinc-950 text-white lg:flex">
                {{-- Multi-layer gradient backdrop --}}
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_20%_20%,rgba(16,185,129,0.20),transparent_40%),radial-gradient(ellipse_at_80%_10%,rgba(59,130,246,0.18),transparent_40%),radial-gradient(ellipse_at_50%_80%,rgba(139,92,246,0.12),transparent_45%)]"></div>

                {{-- Dot grid overlay --}}
                <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>

                <div class="relative flex w-full flex-col justify-between p-12 xl:p-20">
                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center gap-4 font-bold tracking-tight text-xl" wire:navigate>
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20 backdrop-blur-md shadow-2xl">
                            <x-app-logo-icon class="size-6 fill-current" />
                        </span>
                        <span class="text-white">{{ config('app.name', 'Support Desk') }}</span>
                    </a>

                    {{-- Hero content --}}
                    <div class="max-w-xl">
                        <div class="mb-8 inline-flex items-center gap-3 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 backdrop-blur-md">
                            <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-sm font-bold tracking-wide text-emerald-300 uppercase">{{ __('Enterprise support desk') }}</span>
                        </div>

                        <h1 class="text-5xl font-extrabold leading-[1.1] tracking-tight xl:text-6xl">
                            {{ __('Customer support operations,') }}<br/>
                            <span class="bg-gradient-to-r from-emerald-400 to-blue-400 bg-clip-text text-transparent mt-2 inline-block">
                                {{ __('secured for every company.') }}
                            </span>
                        </h1>

                        <p class="mt-8 max-w-lg text-lg leading-8 text-zinc-300/90 font-medium">
                            {{ __('Tickets, complaints, inquiries, notifications, mailbox, and SLA signals all work inside a tenant-safe SaaS workspace.') }}
                        </p>
                    </div>

                    {{-- Feature stats --}}
                    <div class="grid max-w-lg grid-cols-3 gap-6">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-transform hover:scale-105">
                            <p class="text-3xl font-extrabold">24/7</p>
                            <p class="mt-2 text-sm font-semibold tracking-wide text-zinc-400 uppercase">{{ __('Realtime ready') }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-transform hover:scale-105">
                            <p class="text-3xl font-extrabold">{{ __('SLA') }}</p>
                            <p class="mt-2 text-sm font-semibold tracking-wide text-zinc-400 uppercase">{{ __('Breach tracking') }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition-transform hover:scale-105">
                            <p class="text-3xl font-extrabold">{{ __('Roles') }}</p>
                            <p class="mt-2 text-sm font-semibold tracking-wide text-zinc-400 uppercase">{{ __('Scoped access') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Right form panel ── --}}
            <section class="flex min-h-svh flex-col items-center justify-center bg-zinc-50 p-6 dark:bg-zinc-950 sm:p-12">
                <div class="w-full max-w-[400px]">
                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" class="mb-12 flex items-center justify-center gap-4 font-bold tracking-tight text-xl text-zinc-900 dark:text-white lg:hidden" wire:navigate>
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-zinc-900 text-white shadow-xl dark:bg-white dark:text-zinc-950">
                            <x-app-logo-icon class="size-6 fill-current" />
                        </span>
                        <span>{{ config('app.name', 'Support Desk') }}</span>
                    </a>

                    {{-- Auth form card --}}
                    <div class="rounded-3xl border border-zinc-200/60 bg-white p-8 shadow-2xl shadow-zinc-200/40 dark:border-zinc-800/60 dark:bg-zinc-900 dark:shadow-none sm:p-12">
                        {{ $slot }}
                    </div>

                    <p class="mt-8 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">
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
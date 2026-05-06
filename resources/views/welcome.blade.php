<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-6">
            <nav class="flex items-center justify-between gap-4">
                <x-app-logo href="{{ route('home') }}" />
                <div class="flex gap-2">
                    @auth
                        <a href="{{ route('portal.dashboard') }}" class="rounded-md border border-white/15 px-4 py-2 text-sm hover:bg-white/10">{{ __('Portal') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md border border-white/15 px-4 py-2 text-sm hover:bg-white/10">{{ __('Log in') }}</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-white px-4 py-2 text-sm font-medium text-zinc-950 hover:bg-zinc-100">{{ __('Register') }}</a>
                    @endauth
                </div>
            </nav>

            <section class="grid flex-1 items-center gap-10 py-16 lg:grid-cols-[1fr_26rem]">
                <div>
                    <p class="text-sm font-medium uppercase tracking-normal text-emerald-300">{{ __('Enterprise support desk') }}</p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight sm:text-5xl">{{ __('Support, complaints, inquiries, and knowledge in one secure workspace.') }}</h1>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-zinc-300">{{ __('Customers can open requests and follow progress while support teams manage work with tenant-safe visibility, roles, and realtime updates.') }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('portal.dashboard') }}" class="rounded-md bg-emerald-400 px-5 py-3 text-sm font-medium text-zinc-950 hover:bg-emerald-300">{{ __('Open portal') }}</a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-md bg-emerald-400 px-5 py-3 text-sm font-medium text-zinc-950 hover:bg-emerald-300">{{ __('Create customer account') }}</a>
                            <a href="{{ route('login') }}" class="rounded-md border border-white/15 px-5 py-3 text-sm hover:bg-white/10">{{ __('Team login') }}</a>
                        @endauth
                    </div>
                </div>

                <div class="rounded-lg border border-white/10 bg-white/5 p-5 shadow-2xl shadow-black/30">
                    <div class="flex items-center justify-between border-b border-white/10 pb-4">
                        <span class="text-sm text-zinc-300">{{ __('Today') }}</span>
                        <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs text-emerald-200">{{ __('Realtime ready') }}</span>
                    </div>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-md bg-white/10 p-4">
                            <p class="text-sm text-zinc-300">{{ __('Open tickets') }}</p>
                            <p class="mt-1 text-2xl font-semibold">24</p>
                        </div>
                        <div class="rounded-md bg-white/10 p-4">
                            <p class="text-sm text-zinc-300">{{ __('Waiting customer') }}</p>
                            <p class="mt-1 text-2xl font-semibold">8</p>
                        </div>
                        <div class="rounded-md bg-white/10 p-4">
                            <p class="text-sm text-zinc-300">{{ __('SLA watch') }}</p>
                            <p class="mt-1 text-2xl font-semibold">3</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>

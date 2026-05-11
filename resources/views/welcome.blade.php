<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <head>
    @include('partials.head')
  </head>
  <body class="min-h-screen bg-zinc-950 text-white antialiased selection:bg-emerald-500/30">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-8">
      <nav class="flex items-center justify-between gap-4">
        <x-app-logo href="{{ route('home') }}" class="h-10" />
        <div class="flex items-center gap-3">
          @auth
            <a href="{{ auth()->user()->user_type === \App\Enums\UserType::Customer ? route('portal.dashboard') : route('dashboard') }}" class="rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-bold transition-all hover:bg-white/10 hover:border-white/20">{{ __('Go to Dashboard') }}</a>
          @else
            <a href="{{ route('login') }}" class="rounded-xl border border-white/10 px-5 py-2.5 text-sm font-bold transition-all hover:bg-white/5">{{ __('Log in') }}</a>
            <a href="{{ route('register') }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-zinc-950 transition-all hover:bg-zinc-200">{{ __('Get Started') }}</a>
          @endauth
        </div>
      </nav>

      <section class="flex flex-1 flex-col items-center justify-center py-20 text-center">
        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-4 py-1.5 text-xs font-bold tracking-wide text-emerald-400 uppercase">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
          </span>
          {{ __('Enterprise Support Suite') }}
        </div>

        <h1 class="mt-8 max-w-4xl text-5xl font-black leading-[1.1] tracking-tight sm:text-7xl">
          {{ __('Support that feels') }} <span class="bg-gradient-to-r from-emerald-400 to-blue-500 bg-clip-text text-transparent">{{ __('effortless') }}</span>.
        </h1>
        
        <p class="mt-8 max-w-2xl text-lg font-medium leading-relaxed text-zinc-400 sm:text-xl">
          {{ __('A secure, multi-tenant workspace for complaints, inquiries, and knowledge management. Built for scale, designed for simplicity.') }}
        </p>

        <div class="mt-12 flex flex-wrap justify-center gap-4">
          @auth
            <a href="{{ auth()->user()->user_type === \App\Enums\UserType::Customer ? route('portal.dashboard') : route('dashboard') }}" class="rounded-2xl bg-emerald-500 px-8 py-4 text-base font-black text-zinc-950 transition-all hover:bg-emerald-400 hover:scale-105 active:scale-95 shadow-lg shadow-emerald-500/20">{{ __('Open Portal') }}</a>
          @else
            <a href="{{ route('register') }}" class="rounded-2xl bg-emerald-500 px-8 py-4 text-base font-black text-zinc-950 transition-all hover:bg-emerald-400 hover:scale-105 active:scale-95 shadow-lg shadow-emerald-500/20">{{ __('Create Customer Account') }}</a>
            <a href="{{ route('login') }}" class="rounded-2xl border border-white/10 bg-white/5 px-8 py-4 text-base font-black transition-all hover:bg-white/10 hover:border-white/20 active:scale-95">{{ __('Team Member Login') }}</a>
          @endauth
        </div>

        <div class="mt-20 grid w-full max-w-4xl grid-cols-2 gap-8 border-t border-white/5 pt-12 md:grid-cols-4">
          <div>
            <p class="text-3xl font-black text-white">99.9%</p>
            <p class="mt-1 text-sm font-bold uppercase tracking-wider text-zinc-500">{{ __('Uptime') }}</p>
          </div>
          <div>
            <p class="text-3xl font-black text-white">24/7</p>
            <p class="mt-1 text-sm font-bold uppercase tracking-wider text-zinc-500">{{ __('Monitoring') }}</p>
          </div>
          <div>
            <p class="text-3xl font-black text-white">AES-256</p>
            <p class="mt-1 text-sm font-bold uppercase tracking-wider text-zinc-500">{{ __('Encryption') }}</p>
          </div>
          <div>
            <p class="text-3xl font-black text-white">2FA</p>
            <p class="mt-1 text-sm font-bold uppercase tracking-wider text-zinc-500">{{ __('Secure Auth') }}</p>
          </div>
        </div>
      </section>

      <footer class="mt-auto border-t border-white/5 py-8 text-center">
        <p class="text-sm font-medium text-zinc-600">
          &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
        </p>
      </footer>
    </main>
  </body>
</html>

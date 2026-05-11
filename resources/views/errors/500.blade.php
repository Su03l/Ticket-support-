@php($status = 500)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
  <head>
    @include('partials.head')
  </head>
  <body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <main class="flex min-h-screen items-center justify-center p-6">
      <section class="w-full max-w-lg rounded-lg border border-zinc-200 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-rose-100 text-2xl font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-200">{{ $status }}</div>
        <h1 class="mt-6 text-2xl font-semibold">{{ __('Something went wrong') }}</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('The team can use the error number to find this issue quickly.') }}</p>
        <p class="mt-2 text-xs text-zinc-500">{{ __('Error number') }}: {{ $status }}{{ isset($errorId) ? ' · '.$errorId : '' }}</p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
          <a href="{{ url()->previous() }}" class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('Go back') }}</a>
          <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">{{ __('Home') }}</a>
        </div>
      </section>
    </main>
  </body>
</html>

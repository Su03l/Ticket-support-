@props([
    'title'       => '',
    'description' => '',
    'breadcrumbs' => [],
])

<div class="flex flex-col gap-2">
    {{-- Breadcrumbs --}}
    @if (count($breadcrumbs) > 0)
        <nav class="mb-2 flex items-center gap-2 text-xs font-semibold text-zinc-400 dark:text-zinc-500">
            @foreach ($breadcrumbs as $crumb)
                @if (!$loop->last)
                    @if (isset($crumb['href']))
                        <a href="{{ $crumb['href'] }}" wire:navigate class="transition-colors hover:text-zinc-900 dark:hover:text-white">{{ $crumb['label'] }}</a>
                    @else
                        <span>{{ $crumb['label'] }}</span>
                    @endif
                    <flux:icon name="chevron-right" class="size-3 shrink-0 opacity-50" />
                @else
                    <span class="text-zinc-900 dark:text-white">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1.5 text-base font-medium text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        @if ($actions ?? false)
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
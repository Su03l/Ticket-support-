@props([
    'title'       => '',
    'description' => '',
    'breadcrumbs' => [],
])

<div class="flex flex-col gap-1">
    {{-- Breadcrumbs --}}
    @if (count($breadcrumbs) > 0)
        <nav class="mb-1 flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500">
            @foreach ($breadcrumbs as $crumb)
                @if (!$loop->last)
                    @if (isset($crumb['href']))
                        <a href="{{ $crumb['href'] }}" wire:navigate class="font-medium transition-colors hover:text-zinc-600 dark:hover:text-zinc-300">{{ $crumb['label'] }}</a>
                    @else
                        <span class="font-medium">{{ $crumb['label'] }}</span>
                    @endif
                    <flux:icon name="chevron-right" class="size-3 shrink-0" />
                @else
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-2xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        @if ($actions ?? false)
            <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

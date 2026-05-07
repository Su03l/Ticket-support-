@props([
    'icon'        => 'inbox',
    'heading'     => '',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'col-span-full flex flex-col items-center justify-center rounded-[2.5rem] border border-zinc-200/60 bg-gradient-to-b from-white to-zinc-50/50 px-8 py-20 text-center shadow-sm dark:border-zinc-800/60 dark:from-zinc-900/60 dark:to-zinc-900/30']) }}>
    <div class="mb-6 flex size-20 items-center justify-center rounded-[2rem] bg-white shadow-xl shadow-zinc-200/50 ring-1 ring-zinc-200/80 dark:bg-zinc-900 dark:shadow-none dark:ring-zinc-800">
        <flux:icon :name="$icon" class="size-9 text-zinc-400 dark:text-zinc-500" />
    </div>

    <flux:heading size="lg" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $heading }}</flux:heading>

    @if ($description)
        <flux:text class="mt-3 max-w-sm text-base leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
    @endif

    @if ($action ?? false)
        <div class="mt-8">
            {{ $action }}
        </div>
    @endif
</div>
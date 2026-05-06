@props([
    'icon'        => 'inbox',
    'heading'     => '',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'col-span-full flex flex-col items-center justify-center rounded-xl bg-gradient-to-b from-zinc-50 to-zinc-100/50 px-6 py-16 text-center dark:from-zinc-900/60 dark:to-zinc-900/30']) }}>
    <div class="mb-5 flex size-16 items-center justify-center rounded-2xl bg-white shadow-[0_2px_8px_0_rgb(0,0,0,0.06)] ring-1 ring-zinc-200/80 dark:bg-zinc-900 dark:ring-zinc-800">
        <flux:icon :name="$icon" class="size-7 text-zinc-400 dark:text-zinc-500" />
    </div>

    <flux:heading size="md" class="font-semibold tracking-tight text-zinc-800 dark:text-zinc-200">{{ $heading }}</flux:heading>

    @if ($description)
        <flux:text class="mt-2 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
    @endif

    @if ($action ?? false)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
</div>

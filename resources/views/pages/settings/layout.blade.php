<div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
    {{-- Settings sidebar nav --}}
    <aside class="card p-2">
        <p class="mb-1 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
            {{ __('Settings') }}
        </p>
        <flux:navlist aria-label="{{ __('Settings') }}" class="grid gap-0.5">
            <flux:navlist.item icon="user-circle" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                {{ __('Profile') }}
            </flux:navlist.item>
            <flux:navlist.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>
                {{ __('Security') }}
            </flux:navlist.item>
            <flux:navlist.item icon="swatch" :href="route('appearance.edit')" :current="request()->routeIs('appearance.edit')" wire:navigate>
                {{ __('Appearance') }}
            </flux:navlist.item>
            @if (auth()->user()->company_id !== null && auth()->user()->canAny(['settings.view', 'branding.view']))
                <flux:navlist.item icon="building-office-2" :href="route('company-settings.edit')" :current="request()->routeIs('company-settings.edit')" wire:navigate>
                    {{ __('Company') }}
                </flux:navlist.item>
            @endif
            @canany(['file_policies.view', 'file_policies.update'])
                <flux:navlist.item icon="folder" :href="route('file-policies.edit')" :current="request()->routeIs('file-policies.edit')" wire:navigate>
                    {{ __('Files') }}
                </flux:navlist.item>
            @endcanany
            @canany(['settings.view', 'settings.update'])
                <flux:navlist.item icon="clock" :href="route('working-hours.edit')" :current="request()->routeIs('working-hours.edit')" wire:navigate>
                    {{ __('Working hours') }}
                </flux:navlist.item>
            @endcanany
        </flux:navlist>
    </aside>

    {{-- Settings content --}}
    <section class="card min-w-0">
        <div class="border-b border-zinc-100/80 px-6 py-5 dark:border-zinc-800/80">
            <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $heading ?? '' }}</h2>
            @if (isset($subheading) && $subheading)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
            @endif
        </div>

        <div class="p-6 sm:p-7">
            <div class="w-full max-w-2xl">
                {{ $slot }}
            </div>
        </div>
    </section>
</div>

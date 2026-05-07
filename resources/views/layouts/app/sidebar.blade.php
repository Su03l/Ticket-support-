@php
    $user = auth()->user();

    $navigationGroups = [
        __('Workspace') => [
            ['label' => __('Dashboard'), 'icon' => 'home', 'route' => 'dashboard', 'active' => 'dashboard', 'permission' => null],
            ['label' => __('Customer portal'), 'icon' => 'sparkles', 'route' => 'portal.dashboard', 'active' => 'portal.*', 'permission' => ['tickets.view.own', 'complaints.view.own', 'inquiries.view.own'], 'user_type' => \App\Enums\UserType::Customer->value],
            ['label' => __('Tickets'), 'icon' => 'ticket', 'route' => 'tickets.index', 'active' => 'tickets.*', 'permission' => ['tickets.view', 'tickets.view.own', 'tickets.view.department', 'tickets.view.assigned']],
            ['label' => __('Complaints'), 'icon' => 'exclamation-triangle', 'route' => 'complaints.index', 'active' => 'complaints.*', 'permission' => ['complaints.view', 'complaints.view.own', 'complaints.view.department']],
            ['label' => __('Inquiries'), 'icon' => 'chat-bubble-left-right', 'route' => 'inquiries.index', 'active' => 'inquiries.*', 'permission' => ['inquiries.view', 'inquiries.view.own']],
        ],
        __('Organization') => [
            ['label' => __('Companies'), 'icon' => 'building-office-2', 'route' => 'companies.index', 'active' => 'companies.*', 'permission' => 'companies.view'],
            ['label' => __('Users'), 'icon' => 'users', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.view'],
            ['label' => __('Departments'), 'icon' => 'squares-2x2', 'route' => 'departments.index', 'active' => 'departments.*', 'permission' => 'departments.view'],
            ['label' => __('Roles'), 'icon' => 'shield-check', 'route' => 'roles.index', 'active' => 'roles.*', 'permission' => 'roles.view'],
        ],
        __('Insights') => [
            ['label' => __('Reports'), 'icon' => 'chart-bar', 'route' => 'reports.index', 'active' => 'reports.*', 'permission' => 'reports.view'],
            ['label' => __('HR KPI'), 'icon' => 'presentation-chart-line', 'route' => 'reports.kpis', 'active' => 'reports.kpis', 'permission' => 'reports.view'],
            ['label' => __('Report designer'), 'icon' => 'document-chart-bar', 'route' => 'reports.templates', 'active' => 'reports.templates', 'permission' => 'reports.export'],
            ['label' => __('Files'), 'icon' => 'folder', 'route' => 'files.index', 'active' => 'files.*', 'permission' => ['files.view', 'files.download']],
            ['label' => __('Activity logs'), 'icon' => 'clipboard-document-list', 'route' => 'activity-logs.index', 'active' => 'activity-logs.*', 'permission' => 'activity_logs.view'],
            ['label' => __('Error logs'), 'icon' => 'bug-ant', 'route' => 'error-logs.index', 'active' => 'error-logs.*', 'permission' => 'error_logs.view'],
        ],
        __('Knowledge') => [
            ['label' => __('Canned responses'), 'icon' => 'document-text', 'route' => 'canned-responses.index', 'active' => 'canned-responses.*', 'permission' => 'canned_responses.view'],
            ['label' => __('Knowledge base'), 'icon' => 'book-open', 'route' => 'knowledge-base.index', 'active' => 'knowledge-base.*', 'permission' => 'knowledge_base.view'],
            ['label' => __('FAQ'), 'icon' => 'question-mark-circle', 'route' => 'faqs.index', 'active' => 'faqs.*', 'permission' => 'faq.view'],
            ['label' => __('Custom fields'), 'icon' => 'adjustments-horizontal', 'route' => 'custom-fields.index', 'active' => 'custom-fields.*', 'permission' => 'custom_fields.view'],
        ],
    ];

    $canSee = function (array $item) use ($user): bool {
        if (($item['user_type'] ?? null) !== null && $user->user_type->value !== $item['user_type']) {
            return false;
        }

        if ($item['permission'] === null) {
            return true;
        }

        return is_array($item['permission'])
            ? $user->hasAnyPermission($item['permission'])
            : $user->can($item['permission']);
    };

    $themePreference = $user->theme_preference?->value ?? 'system';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="{{ $themePreference === 'dark' ? 'dark' : '' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 selection:bg-zinc-900 selection:text-white dark:selection:bg-white dark:selection:text-zinc-900">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200/80 bg-white/95 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-900/95">
            <flux:sidebar.header class="border-b border-zinc-200/80 p-4 dark:border-zinc-800/80">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate class="scale-105" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="px-3 py-5">
                @foreach ($navigationGroups as $heading => $items)
                    @php
                        $visibleItems = collect($items)->filter($canSee);
                    @endphp

                    @if ($visibleItems->isNotEmpty())
                        <flux:sidebar.group :heading="$heading" class="grid gap-1 mb-2">
                            @foreach ($visibleItems as $item)
                                <flux:sidebar.item
                                    :icon="$item['icon']"
                                    :href="$item['route'] ?? false ? route($item['route']) : $item['href']"
                                    :current="request()->routeIs($item['active'])"
                                    wire:navigate
                                    class="font-medium tracking-tight rounded-xl px-3 py-2.5 transition-all hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                >
                                    {{ $item['label'] }}
                                </flux:sidebar.item>
                            @endforeach
                        </flux:sidebar.group>
                    @endif
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav class="border-t border-zinc-100/80 px-3 py-4 dark:border-zinc-800">
                @canany(['settings.view', 'branding.view'])
                    <flux:sidebar.item
                        icon="cog-6-tooth"
                        :href="$user->company_id === null ? route('profile.edit') : route('company-settings.edit')"
                        :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit', 'company-settings.edit')"
                        wire:navigate
                        class="font-medium tracking-tight rounded-xl px-3 py-2.5 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                @endcanany
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block px-3 pb-4" />
        </flux:sidebar>

        <flux:header class="sticky top-0 z-20 border-b border-zinc-200/80 bg-white/95 px-6 py-3 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-950/95 sm:px-8 lg:px-10">
            <flux:sidebar.toggle class="lg:hidden mr-4" icon="bars-2" inset="left" />

            <div class="hidden min-w-0 flex-col lg:flex">
                <flux:heading size="lg" class="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                    {{ filled($title ?? null) ? __($title) : __('Dashboard') }}
                </flux:heading>
                <flux:text class="mt-0.5 truncate text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ $user->company?->name ?? __('Platform workspace') }}
                </flux:text>
            </div>

            <flux:spacer />

            <flux:navbar class="me-4 gap-2 rtl:space-x-reverse py-0!">
                @canany(['settings.view', 'branding.view'])
                    <flux:tooltip :content="__('Admin settings')" position="bottom">
                        <flux:navbar.item
                            icon="cog-6-tooth"
                            :href="$user->company_id === null ? route('profile.edit') : route('company-settings.edit')"
                            :label="__('Admin settings')"
                            wire:navigate
                            class="rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        />
                    </flux:tooltip>
                @endcanany

                <flux:dropdown position="bottom" align="end">
                    <flux:tooltip :content="__('Theme')" position="bottom">
                        <flux:navbar.item icon="moon" href="#" :label="__('Theme')" class="rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" />
                    </flux:tooltip>
                    <flux:menu class="min-w-40 rounded-2xl p-2 shadow-xl shadow-zinc-200/40 dark:shadow-zinc-900/50">
                        <flux:menu.item icon="sun" :href="route('appearance.edit')" wire:navigate class="rounded-xl">{{ __('Light') }}</flux:menu.item>
                        <flux:menu.item icon="moon" :href="route('appearance.edit')" wire:navigate class="rounded-xl">{{ __('Dark') }}</flux:menu.item>
                        <flux:menu.item icon="computer-desktop" :href="route('appearance.edit')" wire:navigate class="rounded-xl">{{ __('System') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown position="bottom" align="end">
                    <flux:tooltip :content="__('Language')" position="bottom">
                        <flux:navbar.item icon="language" href="#" :label="__('Language')" class="rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" />
                    </flux:tooltip>
                    <flux:menu class="min-w-40 rounded-2xl p-2 shadow-xl shadow-zinc-200/40 dark:shadow-zinc-900/50">
                        <form method="POST" action="{{ route('language.switch', 'ar') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" class="w-full cursor-pointer rounded-xl font-medium">
                                {{ __('Arabic') }}
                            </flux:menu.item>
                        </form>
                        <form method="POST" action="{{ route('language.switch', 'en') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" class="w-full cursor-pointer rounded-xl font-medium">
                                {{ __('English') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>

                @can('notifications.view')
                    <livewire:navbar.notifications-menu />
                @endcan

                @can('mailbox.view')
                    <livewire:navbar.mailbox-menu />
                @endcan
            </flux:navbar>

            <flux:dropdown position="bottom" align="end">
                <flux:profile :initials="$user->initials()" icon-trailing="chevron-down" class="ms-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 p-1 pr-2 transition-colors" />
                <flux:menu class="min-w-64 rounded-2xl p-2 shadow-xl shadow-zinc-200/40 dark:shadow-zinc-900/50">
                    <div class="flex items-center gap-4 px-4 py-4">
                        <flux:avatar :name="$user->name" :initials="$user->initials()" class="size-12 ring-2 ring-zinc-100 dark:ring-zinc-800" />
                        <div class="grid min-w-0 flex-1 leading-snug">
                            <span class="truncate text-base font-bold tracking-tight text-zinc-900 dark:text-white">{{ $user->name }}</span>
                            <span class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</span>
                        </div>
                    </div>

                    <flux:menu.separator class="my-1" />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate class="rounded-xl px-3 py-2.5 font-medium">
                            {{ __('Profile') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate class="rounded-xl px-3 py-2.5 font-medium">
                            {{ __('Account settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="my-1" />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer rounded-xl px-3 py-2.5 font-bold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="bg-zinc-50 dark:bg-zinc-950">
            <div class="mx-auto flex w-full max-w-[85rem] flex-1 flex-col gap-8 p-6 sm:p-8 lg:p-10">
                {{ $slot }}
            </div>
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
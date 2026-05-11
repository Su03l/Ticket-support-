<div class="flex flex-col gap-8">
 {{-- Settings Header --}}
 <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div>
   <flux:heading size="xl"level="1">{{ $heading ?? __('Settings') }}</flux:heading>
   @if (isset($subheading) && $subheading)
    <flux:subheading class="mt-1 text-base">{{ $subheading }}</flux:subheading>
   @else
    <flux:subheading class="mt-1 text-base">{{ __('Manage your account preferences and system configuration') }}</flux:subheading>
   @endif
  </div>

  <div class="hidden items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2 dark:border-zinc-800 dark:bg-zinc-900 sm:flex">
   <flux:avatar :name="auth()->user()->name":initials="auth()->user()->initials()" size="sm" class="!rounded-lg"/>
   <div class="flex flex-col">
    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ auth()->user()->name }}</span>
    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
   </div>
  </div>
 </div>

 <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
  {{-- Navigation Sidebar --}}
  <aside class="lg:col-span-3">
   <nav class="space-y-1">
    <flux:navlist variant="outline" class="!bg-transparent !p-0">
     <flux:navlist.item 
      icon="user-circle"
      :href="route('profile.edit')"
      :current="request()->routeIs('profile.edit')"
      wire:navigate
     >
      {{ __('Profile') }}
     </flux:navlist.item>

     <flux:navlist.item 
      icon="shield-check"
      :href="route('security.edit')"
      :current="request()->routeIs('security.edit')"
      wire:navigate
     >
      {{ __('Security') }}
     </flux:navlist.item>

     <flux:navlist.item 
      icon="swatch"
      :href="route('appearance.edit')"
      :current="request()->routeIs('appearance.edit')"
      wire:navigate
     >
      {{ __('Appearance') }}
     </flux:navlist.item>

     @if (auth()->user()->company_id !== null && auth()->user()->canAny(['settings.view', 'branding.view']))
      <div class="pt-6 pb-2 px-3">
       <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Organization') }}</span>
      </div>

      <flux:navlist.item 
       icon="building-office-2"
       :href="route('company-settings.edit')"
       :current="request()->routeIs('company-settings.edit')"
       wire:navigate
      >
       {{ __('Company') }}
      </flux:navlist.item>

      @canany(['file_policies.view', 'file_policies.update'])
       <flux:navlist.item 
        icon="folder"
        :href="route('file-policies.edit')"
        :current="request()->routeIs('file-policies.edit')"
        wire:navigate
       >
        {{ __('File Policies') }}
       </flux:navlist.item>
      @endcanany

      @canany(['settings.view', 'settings.update'])
       <flux:navlist.item 
        icon="clock"
        :href="route('working-hours.edit')"
        :current="request()->routeIs('working-hours.edit')"
        wire:navigate
       >
        {{ __('Working Hours') }}
       </flux:navlist.item>
      @endcanany
     @endif
    </flux:navlist>
   </nav>
  </aside>

  {{-- Main Content Area --}}
  <main class="lg:col-span-9">
   <div class="space-y-8">
    {{ $slot }}
   </div>
  </main>
 </div>
</div>

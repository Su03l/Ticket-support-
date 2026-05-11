<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="space-y-6">
 <div class="rounded-xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/30 dark:bg-red-950/20">
  <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
   <div class="space-y-1">
    <flux:heading class="!text-red-600 dark:!text-red-400">{{ __('Delete account') }}</flux:heading>
    <flux:subheading class="!text-red-500/80 dark:!text-red-400/60">{{ __('Permanently delete your account and all associated data.') }}</flux:subheading>
   </div>

   <flux:modal.trigger name="confirm-user-deletion">
    <flux:button variant="danger"data-test="delete-user-button">
     {{ __('Delete account') }}
    </flux:button>
   </flux:modal.trigger>
  </div>

  <div class="mt-4 max-w-2xl">
   <flux:text size="sm" class="!text-red-600/70 dark:!text-red-400/50">
    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
   </flux:text>
  </div>
 </div>

 <livewire:pages::settings.delete-user-modal />
</section>

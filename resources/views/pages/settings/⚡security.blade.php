<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
 use PasswordValidationRules;

 public string $current_password = '';
 public string $password = '';
 public string $password_confirmation = '';

 public bool $canManageTwoFactor;

 public bool $twoFactorEnabled;

 public bool $requiresConfirmation;

 /**
  * Mount the component.
  */
 public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
 {
  $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

  if ($this->canManageTwoFactor) {
   if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
    $disableTwoFactorAuthentication(auth()->user());
   }

   $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
   $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
  }
 }

 /**
  * Update the password for the currently authenticated user.
  */
 public function updatePassword(): void
 {
  try {
   $validated = $this->validate([
    'current_password' => $this->currentPasswordRules(),
    'password' => $this->passwordRules(),
   ]);
  } catch (ValidationException $e) {
   $this->reset('current_password', 'password', 'password_confirmation');

   throw $e;
  }

  Auth::user()->update([
   'password' => $validated['password'],
  ]);

  $this->reset('current_password', 'password', 'password_confirmation');

  Flux::toast(variant: 'success', text: __('Password updated.'));
 }

 /**
  * Handle the two-factor authentication enabled event.
  */
 #[On('two-factor-enabled')]
 public function onTwoFactorEnabled(): void
 {
  $this->twoFactorEnabled = true;
 }

 /**
  * Disable two-factor authentication for the user.
  */
 public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
 {
  $disableTwoFactorAuthentication(auth()->user());

  $this->twoFactorEnabled = false;
 }
}; ?>

<x-pages::settings.layout :heading="__('Security')":subheading="__('Manage your account password and extra layers of protection')">
 {{-- Update Password Card --}}
 <flux:card class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Update password') }}</flux:heading>
   <flux:subheading>{{ __('Ensure your account is using a long, random password to stay secure.') }}</flux:subheading>
  </div>

  <form method="POST" wire:submit="updatePassword" class="space-y-6">
   <div class="max-w-xl space-y-6">
    <flux:input
     wire:model="current_password"
     :label="__('Current password')"
     type="password"
     required
     autocomplete="current-password"
     viewable
     icon="lock-closed"
    />

    <div class="grid gap-6 sm:grid-cols-2">
     <flux:input
      wire:model="password"
      :label="__('New password')"
      type="password"
      required
      autocomplete="new-password"
      viewable
      icon="key"
     />
     <flux:input
      wire:model="password_confirmation"
      :label="__('Confirm password')"
      type="password"
      required
      autocomplete="new-password"
      viewable
      icon="check-badge"
     />
    </div>
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button variant="primary" type="submit" icon="shield-check"data-test="update-password-button">
     {{ __('Update Password') }}
    </flux:button>
   </div>
  </form>
 </flux:card>

 {{-- Two-Factor Authentication Card --}}
 @if ($canManageTwoFactor)
  <flux:card class="space-y-6">
   <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
     <flux:heading size="lg">{{ __('Two-factor authentication') }}</flux:heading>
     <flux:subheading>{{ __('Add additional security to your account using two-factor authentication.') }}</flux:subheading>
    </div>
    <flux:badge :color="$twoFactorEnabled ? 'emerald' : 'zinc'" size="sm" variant="pill" class="self-start sm:self-center">
     {{ $twoFactorEnabled ? __('Enabled') : __('Disabled') }}
    </flux:badge>
   </div>

   <div class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-8 dark:border-zinc-800 dark:bg-zinc-900/30" wire:cloak>
    @if ($twoFactorEnabled)
     <div class="space-y-8">
      <div class="flex items-start gap-4">
       <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
        <flux:icon.shield-check variant="outline" class="size-6"/>
       </div>
       <div class="space-y-1">
        <flux:text font="medium" class="!text-zinc-800 dark:!text-zinc-200">
         {{ __('Two-factor authentication is active') }}
        </flux:text>
        <flux:text size="sm" variant="subtle">
         {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
        </flux:text>
       </div>
      </div>

      <div class="flex flex-wrap gap-4">
       <flux:button
        variant="danger"
        wire:click="disable"
        size="sm"
        icon="x-mark"
       >
        {{ __('Disable 2FA') }}
       </flux:button>
      </div>

      <flux:separator class="!border-zinc-200 dark:!border-zinc-800"/>

      <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
     </div>
    @else
     <div class="space-y-8">
      <div class="flex items-start gap-4">
       <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
        <flux:icon.shield-exclamation variant="outline" class="size-6"/>
       </div>
       <div class="space-y-1">
        <flux:text font="medium" class="!text-zinc-800 dark:!text-zinc-200">
         {{ __('Two-factor authentication is not active') }}
        </flux:text>
        <flux:text size="sm" variant="subtle">
         {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
        </flux:text>
       </div>
      </div>

      <div class="flex">
       <flux:modal.trigger name="two-factor-setup-modal">
        <flux:button
         variant="primary"
         wire:click="$dispatch('start-two-factor-setup')"
         icon="plus"
        >
         {{ __('Enable 2FA') }}
        </flux:button>
       </flux:modal.trigger>
      </div>

      <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation"/>
     </div>
    @endif
   </div>
  </flux:card>
 @endif
</x-pages::settings.layout>

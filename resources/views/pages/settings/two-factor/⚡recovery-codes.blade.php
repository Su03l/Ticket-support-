<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
 #[Locked]
 public array $recoveryCodes = [];

 /**
  * Mount the component.
  */
 public function mount(): void
 {
  $this->loadRecoveryCodes();
 }

 /**
  * Generate new recovery codes for the user.
  */
 public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
 {
  $generateNewRecoveryCodes(auth()->user());

  $this->loadRecoveryCodes();
 }

 /**
  * Load the recovery codes for the user.
  */
 private function loadRecoveryCodes(): void
 {
  $user = auth()->user();

  if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
   try {
    $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
   } catch (Exception) {
    $this->addError('recoveryCodes', 'Failed to load recovery codes');

    $this->recoveryCodes = [];
   }
  }
 }
}; ?>

<div
 class="space-y-6"
 wire:cloak
 x-data="{ showRecoveryCodes: false }"
>
 <div class="space-y-2">
  <div class="flex items-center gap-2">
   <flux:icon.lock-closed variant="outline" class="size-4 text-zinc-900 dark:text-white"/>
   <flux:heading size="md">{{ __('Recovery codes') }}</flux:heading>
  </div>
  <flux:text variant="subtle" size="sm">
   {{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
  </flux:text>
 </div>

 <div class="space-y-4">
  <div class="flex flex-wrap gap-3">
   <flux:button
    size="sm"
    icon="eye"
    variant="ghost"
    x-show="!showRecoveryCodes"
    @click="showRecoveryCodes = true;"
   >
    {{ __('Show codes') }}
   </flux:button>

   <flux:button
    size="sm"
    icon="eye-slash"
    variant="ghost"
    x-show="showRecoveryCodes"
    @click="showRecoveryCodes = false"
   >
    {{ __('Hide codes') }}
   </flux:button>

   @if (filled($recoveryCodes))
    <flux:button
     size="sm"
     x-show="showRecoveryCodes"
     icon="arrow-path"
     variant="ghost"
     wire:click="regenerateRecoveryCodes"
    >
     {{ __('Regenerate') }}
    </flux:button>
   @endif
  </div>

  <div
   x-show="showRecoveryCodes"
   x-transition
   id="recovery-codes-section"
   class="space-y-4"
  >
   @error('recoveryCodes')
    <flux:callout variant="danger" icon="x-circle"heading="{{$message}}"/>
   @enderror

   @if (filled($recoveryCodes))
    <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/30">
     <div class="grid grid-cols-2 gap-x-8 gap-y-2 font-mono text-sm">
      @foreach($recoveryCodes as $code)
       <div class="select-all text-zinc-700 dark:text-zinc-300" wire:loading.class="opacity-50">
        {{ $code }}
       </div>
      @endforeach
     </div>
    </div>

    <div class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-amber-800 dark:bg-amber-950/20 dark:text-amber-400">
     <flux:icon.exclamation-triangle size="sm" class="mt-0.5 shrink-0"/>
     <flux:text size="xs" color="inherit">
      {{ __('Each recovery code can be used once. After use, it becomes invalid. You can regenerate new codes at any time.') }}
     </flux:text>
    </div>
   @endif
  </div>
 </div>
</div>

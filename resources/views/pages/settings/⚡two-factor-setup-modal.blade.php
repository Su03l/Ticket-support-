<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
 #[Locked]
 public bool $requiresConfirmation;

 #[Locked]
 public string $qrCodeSvg = '';

 #[Locked]
 public string $manualSetupKey = '';

 public bool $showVerificationStep = false;

 public bool $setupComplete = false;

 #[Validate('required|string|size:6', onUpdate: false)]
 public string $code = '';

 /**
  * Mount the component.
  */
 public function mount(bool $requiresConfirmation): void
 {
  $this->requiresConfirmation = $requiresConfirmation;
 }

 #[On('start-two-factor-setup')]
 public function startTwoFactorSetup(): void
 {
  $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
  $enableTwoFactorAuthentication(auth()->user());

  $this->loadSetupData();
 }

 /**
  * Load the two-factor authentication setup data for the user.
  */
 private function loadSetupData(): void
 {
  $user = auth()->user()?->fresh();

  try {
   if (! $user || ! $user->two_factor_secret) {
    throw new Exception('Two-factor setup secret is not available.');
   }

   $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
   $this->manualSetupKey = decrypt($user->two_factor_secret);
  } catch (Exception) {
   $this->addError('setupData', 'Failed to fetch setup data.');

   $this->reset('qrCodeSvg', 'manualSetupKey');
  }
 }

 /**
  * Show the two-factor verification step if necessary.
  */
 public function showVerificationIfNecessary(): void
 {
  if ($this->requiresConfirmation) {
   $this->showVerificationStep = true;

   $this->resetErrorBag();

   return;
  }

  $this->closeModal();
  $this->dispatch('two-factor-enabled');
 }

 /**
  * Confirm two-factor authentication for the user.
  */
 public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
 {
  $this->validate();

  $confirmTwoFactorAuthentication(auth()->user(), $this->code);

  $this->setupComplete = true;

  $this->closeModal();

  $this->dispatch('two-factor-enabled');
 }

 /**
  * Reset two-factor verification state.
  */
 public function resetVerification(): void
 {
  $this->reset('code', 'showVerificationStep');

  $this->resetErrorBag();
 }

 /**
  * Close the two-factor authentication modal.
  */
 public function closeModal(): void
 {
  $this->reset(
   'code',
   'manualSetupKey',
   'qrCodeSvg',
   'showVerificationStep',
   'setupComplete',
  );

  $this->resetErrorBag();
 }

 /**
  * Get the current modal configuration state.
  */
 #[Computed]
 public function modalConfig(): array
 {
  if ($this->setupComplete) {
   return [
    'title' => __('Two-factor authentication enabled'),
    'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
    'buttonText' => __('Close'),
   ];
  }

  if ($this->showVerificationStep) {
   return [
    'title' => __('Verify authentication code'),
    'description' => __('Enter the 6-digit code from your authenticator app.'),
    'buttonText' => __('Continue'),
   ];
  }

  return [
   'title' => __('Enable two-factor authentication'),
   'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
   'buttonText' => __('Continue'),
  ];
 }
}; ?>

<flux:modal
 name="two-factor-setup-modal"
 class="max-w-md"
 @close="closeModal"
>
 <div class="space-y-8">
  {{-- Modal Header --}}
  <div class="flex flex-col items-center space-y-4">
   <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
    <flux:icon.qr-code class="size-8 text-zinc-900 dark:text-white"/>
   </div>

   <div class="space-y-1 text-center">
    <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
    <flux:subheading>{{ $this->modalConfig['description'] }}</flux:subheading>
   </div>
  </div>

  @if ($showVerificationStep)
   {{-- Verification Step --}}
   <div class="space-y-8"x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
    <div class="flex justify-center">
     <flux:otp
      name="code"
      wire:model="code"
      length="6"
      label="OTP Code"
      label:sr-only
      class="mx-auto"
     />
    </div>

    <div class="grid grid-cols-2 gap-3">
     <flux:button
      variant="ghost"
      wire:click="resetVerification"
     >
      {{ __('Back') }}
     </flux:button>

     <flux:button
      variant="primary"
      wire:click="confirmTwoFactor"
      x-bind:disabled="$wire.code.length < 6"
     >
      {{ __('Confirm') }}
     </flux:button>
    </div>
   </div>
  @else
   {{-- Setup Step --}}
   <div class="space-y-8">
    @error('setupData')
     <flux:callout variant="danger" icon="x-circle"heading="{{ $message }}"/>
    @enderror

    <div class="flex justify-center">
     <div class="relative flex size-52 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800">
      @empty($qrCodeSvg)
       <div class="absolute inset-0 flex items-center justify-center animate-pulse">
        <flux:icon.loading class="size-8"/>
       </div>
      @else
       <div x-data class="flex items-center justify-center h-full">
        <div
         class="bg-white"
         :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
        >
         {!! $qrCodeSvg !!}
        </div>
       </div>
      @endempty
     </div>
    </div>

    <div class="space-y-6">
     <flux:button
      :disabled="$errors->has('setupData')"
      variant="primary"
      class="w-full"
      wire:click="showVerificationIfNecessary"
     >
      {{ $this->modalConfig['buttonText'] }}
     </flux:button>

     <div class="relative">
      <div class="absolute inset-0 flex items-center"aria-hidden="true">
       <div class="w-full border-t border-zinc-200 dark:border-zinc-800"></div>
      </div>
      <div class="relative flex justify-center text-xs uppercase">
       <span class="bg-white px-2 text-zinc-500 dark:bg-zinc-900">{{ __('Or manual entry') }}</span>
      </div>
     </div>

     <div
      class="space-y-3"
      x-data="{
       copied: false,
       async copy() {
        try {
         await navigator.clipboard.writeText('{{ $manualSetupKey }}');
         this.copied = true;
         setTimeout(() => this.copied = false, 2000);
        } catch (e) {
         console.warn('Could not copy to clipboard');
        }
       }
      }"
     >
      <flux:field>
       <flux:label size="sm">{{ __('Setup key') }}</flux:label>
       <div class="flex items-stretch gap-2">
        <flux:input
         type="text"
         readonly
         value="{{ $manualSetupKey }}"
         variant="filled"
         class="font-mono text-sm flex-1"
        />

        <flux:button
         variant="ghost"
         @click="copy()"
         icon="document-duplicate"
         ::class="copied ? 'text-emerald-600' : ''"
         square
        />
       </div>
      </flux:field>
     </div>
    </div>
   </div>
  @endif
 </div>
</flux:modal>

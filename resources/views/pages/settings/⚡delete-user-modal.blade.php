<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
 use PasswordValidationRules;

 public string $password = '';

 /**
  * Delete the currently authenticated user.
  */
 public function deleteUser(Logout $logout): void
 {
  $this->validate([
   'password' => $this->currentPasswordRules(),
  ]);

  tap(Auth::user(), $logout(...))->delete();

  $this->redirect('/', navigate: true);
 }
}; ?>

<flux:modal name="confirm-user-deletion":show="$errors->isNotEmpty()"focusable class="max-w-md">
 <form method="POST" wire:submit="deleteUser" class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Confirm Account Deletion') }}</flux:heading>

   <flux:subheading class="mt-2">
    {{ __('This action is permanent and cannot be undone. All your data will be wiped from our systems. Please enter your password to confirm.') }}
   </flux:subheading>
  </div>

  <flux:input 
   wire:model="password"
   :label="__('Confirm Password')"
   type="password"
   placeholder="{{ __('Enter your password') }}"
   viewable 
  />

  <div class="flex justify-end gap-3">
   <flux:modal.close>
    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
   </flux:modal.close>

   <flux:button variant="danger" type="submit"data-test="confirm-delete-user-button">
    {{ __('Permanently delete account') }}
   </flux:button>
  </div>
 </form>
</flux:modal>

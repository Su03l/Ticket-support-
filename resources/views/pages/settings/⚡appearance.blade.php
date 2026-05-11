<?php

use App\Enums\ThemePreference;
use App\Services\UserProfileService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
 use AuthorizesRequests;

 public string $themePreference = 'system';

 public function mount(): void
 {
  $this->themePreference = Auth::user()->theme_preference?->value ?? ThemePreference::System->value;
 }

 public function updateAppearance(UserProfileService $profiles): void
 {
  $user = Auth::user();

  $this->authorize('updatePreferences', $user);

  $validated = $this->validate([
   'themePreference' => ['required', 'string', 'in:light,dark,system'],
  ]);

  $profiles->updatePreferences($user, [
   'theme_preference' => $validated['themePreference'],
   'notification_preferences' => $user->notification_preferences ?? $profiles->defaultNotificationPreferences(),
  ]);

  Flux::toast(variant: 'success', text: __('Appearance updated.'));
 }
}; ?>

<x-pages::settings.layout :heading="__('Appearance')":subheading="__('Customize how the application looks and feels on your device')">
 <flux:card class="space-y-8">
  <div>
   <flux:heading size="lg">{{ __('Theme Preference') }}</flux:heading>
   <flux:subheading>{{ __('Choose your preferred color scheme or let your system decide.') }}</flux:subheading>
  </div>

  <form wire:submit="updateAppearance" class="space-y-8">
   <div class="max-w-lg rounded-2xl border border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-800 dark:bg-zinc-900/30">
    <flux:radio.group x-data variant="segmented"x-model="$flux.appearance" wire:model="themePreference">
     <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
     <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
     <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
    </flux:radio.group>
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button variant="primary" type="submit" icon="swatch">
     {{ __('Save Appearance') }}
    </flux:button>
   </div>
  </form>
 </flux:card>
</x-pages::settings.layout>

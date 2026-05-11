<?php

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
 use AuthorizesRequests, ProfileValidationRules, WithFileUploads;

 public string $name = '';
 public string $email = '';
 public string $locale = 'ar';
 public array $notificationPreferences = [];
 public $avatarUpload = null;

 /**
  * Mount the component.
  */
 public function mount(UserProfileService $profiles): void
 {
  $user = Auth::user();

  $this->name = $user->name;
  $this->email = $user->email;
  $this->locale = $user->locale ?? 'ar';
  $this->notificationPreferences = array_replace(
   $profiles->defaultNotificationPreferences(),
   $user->notification_preferences ?? [],
  );
 }

 /**
  * Update the profile information for the currently authenticated user.
  */
 public function updateProfileInformation(UserProfileService $profiles): void
 {
  $user = Auth::user();

  $this->authorize('update', $user);

  $validated = $this->validate($this->profileRules($user->id));

  $profiles->updateProfile($user, $validated);

  Flux::toast(variant: 'success', text: __('Profile updated.'));
 }

 public function updateAvatar(UserProfileService $profiles): void
 {
  $user = Auth::user();

  $this->authorize('updateAvatar', $user);

  $validated = $this->validate([
   'avatarUpload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=2048,max_height=2048'],
  ]);

  $profiles->updateAvatar($user, $validated['avatarUpload']);

  $this->reset('avatarUpload');

  Flux::toast(variant: 'success', text: __('Avatar updated.'));
 }

 public function deleteAvatar(UserProfileService $profiles): void
 {
  $user = Auth::user();

  $this->authorize('updateAvatar', $user);

  $profiles->deleteAvatar($user);

  Flux::toast(variant: 'success', text: __('Avatar removed.'));
 }

 public function updatePreferences(UserProfileService $profiles): void
 {
  $user = Auth::user();

  $this->authorize('updatePreferences', $user);

  $validated = $this->validate([
   'locale' => ['required', 'string', 'in:ar,en'],
   'notificationPreferences.email' => ['required', 'boolean'],
   'notificationPreferences.database' => ['required', 'boolean'],
   'notificationPreferences.realtime' => ['required', 'boolean'],
  ]);

  $profiles->updatePreferences($user, [
   'locale' => $validated['locale'],
   'notification_preferences' => $validated['notificationPreferences'],
  ]);

  session()->put('locale', $validated['locale']);
  app()->setLocale($validated['locale']);

  Flux::toast(variant: 'success', text: __('Preferences updated.'));

  $this->redirectRoute('profile.edit', navigate: true);
 }

 /**
  * Send an email verification notification to the current user.
  */
 public function resendVerificationNotification(): void
 {
  $user = Auth::user();

  if ($user->hasVerifiedEmail()) {
   $this->redirectIntended(default: route('dashboard', absolute: false));

   return;
  }

  $user->sendEmailVerificationNotification();

  Flux::toast(text: __('A new verification link has been sent to your email address.'));
 }

 #[Computed]
 public function hasUnverifiedEmail(): bool
 {
  return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
 }

 #[Computed]
 public function showDeleteUser(): bool
 {
  return ! Auth::user() instanceof MustVerifyEmail
   || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
 }

 #[Computed]
 public function avatarUrl(): ?string
 {
  return Auth::user()->avatar === null
   ? null
   : Storage::disk('public')->url(Auth::user()->avatar);
 }
}; ?>

<x-pages::settings.layout :heading="__('Profile')":subheading="__('Update your account profile and preferences')">
 {{-- Personal Information Card --}}
 <flux:card class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Personal information') }}</flux:heading>
   <flux:subheading>{{ __('Manage your basic profile details and contact information.') }}</flux:subheading>
  </div>

  <form wire:submit="updateProfileInformation" class="space-y-6">
   <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-800 dark:bg-zinc-900/30">
    <div class="flex items-center gap-4">
     <div class="relative">
      @if ($this->avatarUrl)
       <img src="{{ $this->avatarUrl }}"alt="{{ auth()->user()->name }}" class="size-16 rounded-2xl border border-zinc-200 object-cover dark:border-zinc-800">
      @else
       <flux:avatar :name="auth()->user()->name":initials="auth()->user()->initials()" size="xl" class="!rounded-2xl"/>
      @endif
     </div>
     <div class="min-w-0">
      <flux:heading size="md">{{ auth()->user()->name }}</flux:heading>
      <flux:text class="truncate text-sm">{{ auth()->user()->email }}</flux:text>
     </div>
    </div>
    <flux:badge color="emerald" size="sm" variant="pill" class="self-start sm:self-center">{{ __(str_replace('_', ' ', auth()->user()->user_type->value)) }}</flux:badge>
   </div>

   <div class="grid gap-6 sm:grid-cols-2">
    <flux:input wire:model="name":label="__('Full Name')" type="text"required autofocus autocomplete="name" icon="user"/>

    <div class="space-y-2">
     <flux:input wire:model="email":label="__('Email Address')" type="email"required autocomplete="email" icon="envelope"/>

     @if ($this->hasUnverifiedEmail)
      <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-950/20">
       <flux:text variant="subtle" size="xs" class="!text-amber-700 dark:!text-amber-400">
        {{ __('Your email address is unverified.') }}
        <flux:button variant="ghost" size="xs" class="!text-amber-700 hover:!text-amber-800 dark:!text-amber-400 dark:hover:!text-amber-300 px-1" wire:click.prevent="resendVerificationNotification">
         {{ __('Re-send verification email') }}
        </flux:button>
       </flux:text>
      </div>
     @endif
    </div>
   </div>

   <div class="flex justify-end">
    <flux:button variant="primary" type="submit" icon="check"data-test="update-profile-button">
     {{ __('Save Changes') }}
    </flux:button>
   </div>
  </form>
 </flux:card>

 {{-- Avatar Card --}}
 <flux:card class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Avatar') }}</flux:heading>
   <flux:subheading>{{ __('Upload a profile picture to help identify you in the system.') }}</flux:subheading>
  </div>

  <form wire:submit="updateAvatar" class="space-y-6">
   <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
    <div class="flex-1 max-w-md">
     <flux:input wire:model="avatarUpload":label="__('Choose image')" type="file"accept="image/jpeg,image/png,image/webp"/>
    </div>

    <div class="flex items-center gap-3">
     <flux:button variant="primary" type="submit":disabled="!$avatarUpload">
      {{ __('Upload Avatar') }}
     </flux:button>

     @if (auth()->user()->avatar)
      <flux:button variant="ghost" type="button" wire:click="deleteAvatar" color="danger">
       {{ __('Remove') }}
      </flux:button>
     @endif
    </div>
   </div>
   
   <flux:text size="xs" variant="subtle">
    {{ __('JPG, PNG or WebP. Max 2MB. Minimum 64x64px.') }}
   </flux:text>
  </form>
 </flux:card>

 {{-- Preferences Card --}}
 <flux:card class="space-y-6">
  <div>
   <flux:heading size="lg">{{ __('Regional & Notifications') }}</flux:heading>
   <flux:subheading>{{ __('Set your preferred language and how you want to be notified.') }}</flux:subheading>
  </div>

  <form wire:submit="updatePreferences" class="space-y-8">
   <div class="grid gap-8 sm:grid-cols-2">
    <flux:select wire:model="locale":label="__('Default Language')" icon="language">
     <flux:select.option value="ar">{{ __('Arabic') }}</flux:select.option>
     <flux:select.option value="en">{{ __('English') }}</flux:select.option>
    </flux:select>

    <div class="space-y-4">
     <flux:label>{{ __('Notification Channels') }}</flux:label>
     <div class="grid gap-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-900/10">
      <flux:checkbox wire:model="notificationPreferences.email":label="__('Email Notifications')"description="{{ __('Receive updates via email') }}"/>
      <flux:checkbox wire:model="notificationPreferences.database":label="__('In-app Notifications')"description="{{ __('See notifications in the app header') }}"/>
      <flux:checkbox wire:model="notificationPreferences.realtime":label="__('Real-time Updates')"description="{{ __('Instant UI updates when changes occur') }}"/>
     </div>
    </div>
   </div>

   <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <flux:button variant="primary" type="submit" icon="check">
     {{ __('Save Preferences') }}
    </flux:button>
   </div>
  </form>
 </flux:card>

 @if ($this->showDeleteUser)
  <div class="pt-8 border-t border-zinc-100 dark:border-zinc-800">
   <div class="mb-4">
    <flux:heading size="lg" class="!text-red-600 dark:!text-red-500">{{ __('Danger Zone') }}</flux:heading>
    <flux:subheading>{{ __('Actions that are permanent and cannot be reversed.') }}</flux:subheading>
   </div>
   
   <livewire:pages::settings.delete-user-form />
  </div>
 @endif
</x-pages::settings.layout>

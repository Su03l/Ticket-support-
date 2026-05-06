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

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your account profile and preferences')">
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">
            <div class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                @if ($this->avatarUrl)
                    <img src="{{ $this->avatarUrl }}" alt="{{ auth()->user()->name }}" class="size-16 rounded-full border border-zinc-200 object-cover dark:border-zinc-800">
                @else
                    <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" size="lg" />
                @endif
                    <div class="min-w-0">
                        <flux:heading size="sm">{{ auth()->user()->name }}</flux:heading>
                        <flux:text class="truncate text-sm">{{ auth()->user()->email }}</flux:text>
                    </div>
                </div>
                <flux:badge color="emerald">{{ __(str_replace('_', ' ', auth()->user()->user_type->value)) }}</flux:badge>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                <div>
                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                    @if ($this->hasUnverifiedEmail)
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>

                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>

        <flux:separator class="my-8" />

        <form wire:submit="updateAvatar" class="w-full space-y-6">
            <div>
                <flux:heading size="md">{{ __('Avatar') }}</flux:heading>
                <flux:subheading>{{ __('Upload a square image for menus and internal activity.') }}</flux:subheading>
            </div>
            <flux:input wire:model="avatarUpload" :label="__('Avatar')" type="file" accept="image/jpeg,image/png,image/webp" />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">
                    {{ __('Upload avatar') }}
                </flux:button>

                @if (auth()->user()->avatar)
                    <flux:button variant="ghost" type="button" wire:click="deleteAvatar">
                        {{ __('Remove avatar') }}
                    </flux:button>
                @endif
            </div>
        </form>

        <flux:separator class="my-8" />

        <form wire:submit="updatePreferences" class="w-full space-y-6">
            <div class="grid gap-6 sm:grid-cols-[16rem_1fr]">
                <flux:select wire:model="locale" :label="__('Language')">
                    <flux:select.option value="ar">{{ __('Arabic') }}</flux:select.option>
                    <flux:select.option value="en">{{ __('English') }}</flux:select.option>
                </flux:select>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <flux:label>{{ __('Notifications') }}</flux:label>
                    <div class="mt-3 grid gap-3">
                        <flux:checkbox wire:model="notificationPreferences.email" :label="__('Email notifications')" />
                        <flux:checkbox wire:model="notificationPreferences.database" :label="__('In-app notifications')" />
                        <flux:checkbox wire:model="notificationPreferences.realtime" :label="__('Realtime updates')" />
                    </div>
                </div>
            </div>

            <flux:button variant="primary" type="submit">
                {{ __('Save preferences') }}
            </flux:button>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>

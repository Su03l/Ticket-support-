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

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <form wire:submit="updateAppearance" class="my-6 w-full space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" wire:model="themePreference">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>

            <flux:button variant="primary" type="submit">
                {{ __('Save appearance') }}
            </flux:button>
        </form>
    </x-pages::settings.layout>
</section>

<?php

namespace App\Services;

use App\Enums\ThemePreference;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    /**
     * @param  array{name: string, email: string}  $attributes
     */
    public function updateProfile(User $user, array $attributes): User
    {
        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->refresh();
    }

    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        $previousAvatar = $user->avatar;
        $path = $avatar->store("avatars/{$user->id}", 'public');

        $user->forceFill([
            'avatar' => $path,
        ])->save();

        if ($previousAvatar !== null && $previousAvatar !== $path) {
            Storage::disk('public')->delete($previousAvatar);
        }

        return $user->refresh();
    }

    public function deleteAvatar(User $user): User
    {
        if ($user->avatar !== null) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->forceFill([
            'avatar' => null,
        ])->save();

        return $user->refresh();
    }

    /**
     * @param  array{locale?: string, theme_preference?: ThemePreference|string|null, notification_preferences?: array<string, bool>}  $attributes
     */
    public function updatePreferences(User $user, array $attributes): User
    {
        $user->forceFill([
            'locale' => $attributes['locale'] ?? $user->locale,
            'theme_preference' => $attributes['theme_preference'] ?? $user->theme_preference,
            'notification_preferences' => array_replace(
                $this->defaultNotificationPreferences(),
                $attributes['notification_preferences'] ?? [],
            ),
        ])->save();

        return $user->refresh();
    }

    /**
     * @return array<string, bool>
     */
    public function defaultNotificationPreferences(): array
    {
        return [
            'email' => true,
            'database' => true,
            'realtime' => true,
        ];
    }
}

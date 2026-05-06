<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\CompanySetting;
use App\Models\User;

class CompanySettingPolicy
{
    public function view(User $user, CompanySetting $companySetting): bool
    {
        if ($user->user_type === UserType::SuperAdmin) {
            return $user->can('settings.view') || $user->can('branding.view');
        }

        return $user->company_id === $companySetting->company_id
            && ($user->can('settings.view') || $user->can('branding.view'));
    }

    public function update(User $user, CompanySetting $companySetting): bool
    {
        if ($user->user_type === UserType::SuperAdmin) {
            return $user->can('settings.update') || $user->can('branding.update');
        }

        return $user->company_id === $companySetting->company_id
            && ($user->can('settings.update') || $user->can('branding.update'));
    }

    public function updateTheme(User $user, CompanySetting $companySetting): bool
    {
        return $this->update($user, $companySetting) && $user->can('theme.update');
    }

    public function updateLanguage(User $user, CompanySetting $companySetting): bool
    {
        return $this->update($user, $companySetting) && $user->can('language.update');
    }
}

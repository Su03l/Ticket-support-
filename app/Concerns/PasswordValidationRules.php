<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    // rules for new password
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    // rules for current password
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}

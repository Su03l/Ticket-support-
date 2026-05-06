<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\ErrorLog;
use App\Models\User;

class ErrorLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('error_logs.view') && $user->user_type === UserType::SuperAdmin;
    }

    public function view(User $user, ErrorLog $errorLog): bool
    {
        return $this->viewAny($user);
    }

    public function resolve(User $user, ErrorLog $errorLog): bool
    {
        return $this->view($user, $errorLog);
    }
}

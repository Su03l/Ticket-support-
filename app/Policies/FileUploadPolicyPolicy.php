<?php

namespace App\Policies;

use App\Models\FileUploadPolicy;
use App\Models\User;

class FileUploadPolicyPolicy
{
    public function view(User $user, FileUploadPolicy $fileUploadPolicy): bool
    {
        if ($user->company_id === null && $user->can('file_policies.view')) {
            return true;
        }

        return $user->can('file_policies.view')
            && $user->company_id !== null
            && $user->company_id === $fileUploadPolicy->company_id;
    }

    public function update(User $user, FileUploadPolicy $fileUploadPolicy): bool
    {
        if ($user->company_id === null && $user->can('file_policies.update')) {
            return true;
        }

        return $user->can('file_policies.update')
            && $user->company_id !== null
            && $user->company_id === $fileUploadPolicy->company_id;
    }
}

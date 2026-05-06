<?php

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;

class CustomFieldPolicy
{
    public function viewAny(User $user): bool { return $user->can('custom_fields.view'); }
    public function create(User $user): bool { return $user->can('custom_fields.create'); }
    public function update(User $user, CustomField $field): bool { return $user->can('custom_fields.update') && $field->company_id === $user->company_id; }
    public function delete(User $user, CustomField $field): bool { return $user->can('custom_fields.delete') && $field->company_id === $user->company_id; }
}

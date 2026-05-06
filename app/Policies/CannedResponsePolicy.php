<?php

namespace App\Policies;

use App\Models\CannedResponse;
use App\Models\User;

class CannedResponsePolicy
{
    public function viewAny(User $user): bool { return $user->can('canned_responses.view') || $user->can('tickets.reply'); }
    public function create(User $user): bool { return $user->can('canned_responses.create'); }
    public function update(User $user, CannedResponse $response): bool { return $user->can('canned_responses.update') && $response->company_id === $user->company_id; }
    public function delete(User $user, CannedResponse $response): bool { return $user->can('canned_responses.delete') && $response->company_id === $user->company_id; }
}
